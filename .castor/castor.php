<?php

declare(strict_types=1);

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use TheoD\MusicAutoTagger\Docker\DockerUtils;

use function Castor\Attribute\AsArgument;
use function Castor\capture;
use function Castor\context;
use function Castor\finder;
use function Castor\import;
use function Castor\io;
use function Castor\notify;
use function Castor\run;
use function TheoD\MusicAutoTagger\delayed_fingerprint;
use function TheoD\MusicAutoTagger\docker;
use function TheoD\MusicAutoTagger\fgp;
use function TheoD\MusicAutoTagger\Runner\composer;
use function TheoD\MusicAutoTagger\Runner\pnpm;
use function TheoD\MusicAutoTagger\Runner\qa;
use function TheoD\MusicAutoTagger\Runner\symfony;

import('composer://theod02/castor-class-task');

import(__DIR__ . '/src');
import(dirname(__DIR__) . '/tools/castor.php');

#[AsTask]
function start(bool $force = false): void
{
    if (DockerUtils::isRunningInsideContainer()) {
        io()->note('[start] cannot be run inside container. Skipping.');

        return;
    }

    if (
        ! delayed_fingerprint(
            callback: static fn () => docker()
                ->compose('--profile', 'app', 'build', '--no-cache')
                ->run(),
            fingerprint: static fn () => fgp()->php_docker(),
            force: $force
        )
    ) {
        io()->note('Docker images are already built.');
    }

    docker()->compose('--profile', 'app', 'up', '-d', '--wait')->run();
}

#[AsTask]
function stop(): void
{
    docker()->compose('--profile', 'app', 'down')->run();
}

#[AsTask]
function restart(): void
{
    stop();
    start();
}

#[AsTask]
function install(bool $force = false): void
{
    start();

    io()->title('Installing dependencies');
    io()->section('Composer');
    $forceVendor = $force || ! is_dir(context()->workingDirectory . '/vendor');
    if (! delayed_fingerprint(
        callback: static fn () => composer()->install()->run(),
        fingerprint: static fn () => fgp()->composer(),
        force: $forceVendor || $force
    )) {
        io()->note('Composer dependencies are already installed.');
    } else {
        io()->success('Composer dependencies installed');
    }

    io()->section('QA tools');
    qa()->install();

    db_reset();

    //    io()->section('NPM');
    //    $forceNodeModules = $force || ! is_dir(context()->workingDirectory . '/node_modules');
    //    if (! delayed_fingerprint(
    //        callback: static fn () => pnpm()->install()->run(),
    //        fingerprint: static fn () => fgp()->npm(),
    //        force: $forceNodeModules || $force
    //    )) {
    //        io()->note('NPM dependencies are already installed.');
    //    } else {
    //        io()->success('NPM dependencies installed');
    //    }
    //
    //    pnpm()->add('run', 'build')->run();

    notify('Dependencies installed');
}

#[AsTask(name: 'sync')]
function sync(): void
{
    symfony()->console('spotify:sync')->run();
    symfony()->console('spotify:sync:artists:full')->run();
    symfony()->console('spotify:sync:tracks:full')->run();
    symfony()->console('spotify:sync:tracks:full:audio-feature')->run();
}

#[AsTask]
function shell(
    #[AsOption(name: 'no-check', description: 'Don\'t check the dependencies')]
    bool $noCheck = false, // Not used here, but used in listeners.php,
    #[AsArgument(name: 'cmd', description: 'Command to run')]
    ?string $command = null,
): void {
    docker(context()->withTty())
        ->compose('exec')
        ->add('--user', 'www-data')
        ->add('app', 'fish')
        ->addIf($command !== null, '-c', "\"{$command}\"")
        ->run()
    ;
}

/** @noinspection t */
#[AsTask]
function generate_domain_dir(string $domainName): void
{
    $srcDirectory = context()->workingDirectory . '/src';
    $domainName = ucfirst($domainName);

    $domainDirectory = $srcDirectory . '/' . $domainName;

    if (is_dir($domainDirectory)) {
        io()->error("Domain directory {$domainName} already exists");

        return;
    }

    if (! mkdir($domainDirectory) && ! is_dir($domainDirectory)) {
        throw new RuntimeException(sprintf('Directory "%s" was not created', $domainDirectory));
    }

    $directoryStructure = [
        'Application' => [
            'Command' => [],
            'Query' => [],
        ],
        'Domain' => [
            'Exception' => [],
            'Model' => [],
            'Repository' => [],
            'ValueObject' => [],
        ],
        'Infrastructure' => [
            'ApiPlatform' => [
                'Resource' => [],
                'Payload' => [],
                'Query' => [],
                'State' => [
                    'State' => [],
                    'Processor' => [],
                ],
            ],
            'Doctrine' => [],
        ],
    ];

    foreach ($directoryStructure as $dir => $subDirs) {
        $dir = $domainDirectory . '/' . $dir;
        if (! mkdir($dir) && ! is_dir($dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }

        if (! empty($subDirs)) {
            create_dirs($subDirs, $dir);
        }
    }

    io()->success("Domain directory {$domainName} created");
    io()->listing(array_map(static fn ($key) => $domainDirectory . '/' . $key, array_keys($directoryStructure)));
}

function create_dirs(array $dirs, string $baseDir): void
{
    foreach ($dirs as $dirname => $subDirs) {
        $dir = $baseDir . '/' . $dirname;
        if (! mkdir($dir) && ! is_dir($dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }

        if (! empty($subDirs)) {
            create_dirs($subDirs, $dir);
        }
    }
}

#[AsTask]
function import_sql(): void
{
    $sqlFilename = null;

    $sqlFiles = finder()
        ->in(dirname(__DIR__))
        ->files()
        ->depth(0)
        ->name('*.sql')
        ->name('*.sql.gz')
        ->sortByName()
        ->getIterator()
    ;

    $selectedDump = io()->choice('Select the SQL file to import', iterator_to_array($sqlFiles), $sqlFilename);

    io()->writeln("Importing {$selectedDump}");
    $containerId = capture('docker compose ps -q mariadb');
    run("docker exec -i {$containerId} mysql -uroot -proot app < {$selectedDump}");
    io()->success('SQL file imported');
}

#[AsTask(name: 'ui:install')]
function ui_install(): void
{
    pnpm()->install();
}

#[AsTask(name: 'ui:dev')]
function ui_dev(): void
{
    pnpm(context()->withTty())->add('run', 'dev')->run();
}

#[AsTask(name: 'ui:format')]
function ui_format(): void
{
    docker()
        ->compose('exec')
        ->add('--user', 'www-data')
        ->add('--workdir', '/app/assets')
        ->add('app', 'npx', '@biomejs/biome', 'format', '--write', './src')
        ->run()
    ;
}

#[AsTask(name: 'ui:lint')]
function ui_lint(): void
{
    docker()
        ->compose('exec')
        ->add('--user', 'www-data')
        ->add('--workdir', '/app/assets')
        ->add('app', 'npx', '@biomejs/biome', 'lint', './src')
        ->run()
    ;
}

#[AsTask(name: 'ui:ts')]
function ui_ts(bool $fix = false): void
{
    $run = $fix ? 'ts:fix' : 'ts';
    docker()
        ->compose('exec')
        ->add('--user', 'www-data')
        ->add('--workdir', '/app/assets')
        ->add('app', 'pnpm', 'run', $run)
        ->run()
    ;
}

#[AsTask(name: 'ui:http:schema')]
function ui_http_schema(): void
{
    docker()
        ->compose('exec')
        ->add('--user', 'www-data')
        ->add('--workdir', '/app/assets')
        ->add(
            'app',
            'npx',
            'openapi-typescript',
            'http://mantine-starter-kit.web.localhost/api/docs.json',
            '-o',
            './src/api/schema.d.ts'
        )
        ->run()
    ;
}

#[AsTask(name: 'db:reset')]
function db_reset(): void
{
    // Check if the database app exists
    $output = docker(context()->withQuiet())->compose('exec -it mariadb sh -c "mysql -uroot -proot -e \"SHOW DATABASES\""')->run()->getOutput();
    if (str_contains($output, 'app')) {
        if (io()->confirm('The database "app" already exists. Do you want to drop it?', false) === false) {
            return;
        }
    }

    symfony()->console('doctrine:database:drop', '--force', '--if-exists')->run();
    symfony()->console('doctrine:database:create')->run();
    symfony()->console('doctrine:schema:update', '--force')->run();
    symfony()->console('doctrine:fixtures:load', '--no-interaction')->run();
}
