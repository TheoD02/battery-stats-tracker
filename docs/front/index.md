## API Call management and declaration

# Summary

This document provides a comprehensive guide on managing and declaring API calls in the project. It covers the following topics:

1. **API Client Configuration**: The [`api/client.tsx`](https://github.com/TheoD02/battery-stats-tracker/blob/feat/base/app/assets/src/api/client.tsx) file is responsible for creating and configuring the HTTP client that will be used to make requests to the API. It uses the [`openapi-fetch`](https://github.com/drwpow/openapi-typescript/tree/main/packages/openapi-fetch) library to create a client that is compatible with OpenAPI specifications.

2. **Endpoint Declaration**: For each scope of the API, a separate file is created that exports an object containing the endpoints for that scope. These are then combined into a global [`api/endpoints.tsx`](https://github.com/TheoD02/battery-stats-tracker/blob/feat/base/app/assets/src/api/endpoints.tsx) file.

3. **Query Key Declaration**: Similar to endpoints, keys for the queries for each scope are declared in separate files and then combined into a global [`api/keys.tsx`](https://github.com/TheoD02/battery-stats-tracker/blob/feat/base/app/assets/src/api/keys.tsx) file.

4. **Using Mutations**: Mutations are used to modify data on the server. Custom hooks that use the `useMutation` hook from [`@tanstack/react-query`](https://tanstack.com/query/latest/docs/framework/react/overview) are defined for each mutation.

5. **Using Queries**: Queries are used to fetch data from the server. Custom hooks that use the `useQuery` hook from [`@tanstack/react-query`](https://tanstack.com/query/latest/docs/framework/react/overview) are defined for each query.

6. **Type Generation with [`openapi-typescript`](https://github.com/drwpow/openapi-typescript)**: The project uses the [`openapi-typescript`](https://github.com/drwpow/openapi-typescript) library to generate TypeScript types from the OpenAPI specification. This allows the API client to be strongly typed, which can help catch errors at compile time.

The document also explains the benefits of using [`openapi-typescript`](https://github.com/drwpow/openapi-typescript) to generate types, which include type safety, autocompletion, documentation, up-to-date types, and reduced boilerplate.

### All call are based on [`api/client.tsx`](https://github.com/TheoD02/battery-stats-tracker/blob/feat/base/app/assets/src/api/client.tsx)

The [`client.tsx`](https://github.com/TheoD02/battery-stats-tracker/blob/feat/base/app/assets/src/api/client.tsx) file is responsible for creating and configuring the HTTP client that will be used to make requests to the API. It uses the [`openapi-fetch`](https://github.com/drwpow/openapi-typescript/tree/main/packages/openapi-fetch) library to create a client that is compatible with OpenAPI specifications.

The client is configured with a base URL, default headers, and a query serializer. The base URL is the root URL of the API. The headers include a `Content-Type` of `application/ld+json`. The query serializer is a function that formats query parameters into a string. It uses the `query-string` library to do this.

The client also uses a middleware, `throwOnError`, which throws an error if the response status is 400 or above. This is a way to handle HTTP errors globally.

See the file [api/client.tsx](https://github.com/TheoD02/battery-stats-tracker/blob/feat/base/app/assets/src/api/client.tsx) for more details.

### Declaring [`endpoints.tsx`](https://github.com/TheoD02/battery-stats-tracker/blob/feat/base/app/assets/src/api/tracks/endpoints.tsx) for Each Scope of API

For each scope of the API, you should create a separate file that exports an object containing the endpoints for that scope. For example, for the `tracks` scope, you would create a [`api/tracks/endpoints.tsx`](https://github.com/TheoD02/battery-stats-tracker/blob/feat/base/app/assets/src/api/tracks/endpoints.tsx) file that exports an object like this:

```typescript
export const trackEndpoints = {
    query: {
        getTracks: "/api/tracks",
        getOneTrack: "/api/tracks/{id}",
    },
    mutation: {
        analyzeTrack: "/api/tracks/{id}/analyze",
        scanDirectory: "/api/tracks/scan-directory",
    },
};
```

Then, in the global [`api/endpoints.tsx`](https://github.com/TheoD02/battery-stats-tracker/blob/feat/base/app/assets/src/api/endpoints.tsx) file, you would import all of these objects and combine them into one object that you export:

```typescript
import { trackEndpoints } from "./tracks/endpoints.tsx";

export const endpoints = {
    tracks: trackEndpoints,
};
```

### Declaring Keys for Queries for Each Scope and Regrouping Them into [`keys.tsx`](https://github.com/TheoD02/battery-stats-tracker/blob/feat/base/app/assets/src/api/tracks/keys.tsx) Globally

For each scope of the API, you should create a separate file that exports an object containing the keys for the queries for that scope. For example, for the `tracks` scope, you would create a [`api/tracks/keys.tsx`](https://github.com/TheoD02/battery-stats-tracker/blob/feat/base/app/assets/src/api/tracks/keys.tsx) file that exports an object like this:

```typescript
import { trackEndpoints } from "./endpoints.tsx";

export const tracksKeys = {
    paginatedTracks: (params) => [trackEndpoints.query.getTracks, params],
    oneTrack: (id) => [trackEndpoints.query.getOneTrack, id],
};
```

Then, in the global [`api/keys.tsx`](https://github.com/TheoD02/battery-stats-tracker/blob/feat/base/app/assets/src/api/keys.tsx) file, you would import all of these objects and combine them into one object that you export:

```typescript
import { tracksKeys } from "./tracks/keys.tsx";

export const keys = {
    tracks: tracksKeys,
};
```

### Using Mutations

Mutations are used to modify data on the server. In the [`api/tracks/mutations.tsx`](https://github.com/TheoD02/battery-stats-tracker/blob/feat/base/app/assets/src/api/tracks/mutations.tsx) file, there are two mutations defined: `useAnalyzeTrack` and `useScanDirectory`. These are custom hooks that use the `useMutation` hook from [`@tanstack/react-query`](https://tanstack.com/query/latest/docs/framework/react/overview).

To use these mutations, you would call the hook and then call the returned mutate function with the appropriate parameters. For example:

```typescript
const mutation = useAnalyzeTrack({ reactQuery: { enabled: true } });
mutation.mutate({ id: 'track-id' });
```

### Using Queries

Queries are used to fetch data from the server. In the [`api/tracks/queries.tsx`](https://github.com/TheoD02/battery-stats-tracker/blob/feat/base/app/assets/src/api/tracks/queries.tsx) file, there are two queries defined: `usePaginatedTracks` and `useGetOneTrack`. These are custom hooks that use the `useQuery` hook from [`@tanstack/react-query`](https://tanstack.com/query/latest/docs/framework/react/overview).

To use these queries, you would call the hook and then access the returned data. For example:

```typescript
const { data } = usePaginatedTracks({ params: { page: 1 }, reactQuery: { enabled: true } });
console.log(data);
```

### Using [`openapi-typescript`](https://github.com/drwpow/openapi-typescript) to Generate Types

The project uses the [`openapi-typescript`](https://github.com/drwpow/openapi-typescript) library to generate TypeScript types from the OpenAPI specification. This allows the API client to be strongly typed, which can help catch errors at compile time.

To generate the types, you can run the `castor ui:http:schema` task, which is defined in the `.castor/castor.php` file. This task runs the [`openapi-typescript`](https://github.com/drwpow/openapi-typescript) command with the URL of the API's OpenAPI specification and the output file for the types.

Alternatively, you can run the [`openapi-typescript`](https://github.com/drwpow/openapi-typescript) command directly with the following parameters:

```bash
npx openapi-typescript http://battery-stats-tracker.web.localhost/api/docs.json -o ./src/api/schema.d.ts
```

This will generate a `schema.d.ts` file in the `src/api` directory with the TypeScript types for the API.

---

Why use [`openapi-typescript`](https://github.com/drwpow/openapi-typescript)?

The [`openapi-typescript`](https://github.com/drwpow/openapi-typescript) library provides several benefits for TypeScript developers:

1. **Type Safety**: By generating TypeScript types from your OpenAPI specification, you can ensure that your API client is strongly typed. This means that you can catch potential errors at compile time rather than runtime.

2. **Autocompletion**: With the generated types, your IDE can provide autocompletion for API endpoints, parameters, and response objects. This can significantly speed up your development process.

3. **Documentation**: The generated types can serve as a form of documentation for your API. Developers can quickly see what endpoints are available, what parameters they accept, and what the response objects look like.

4. **Up-to-date Types**: If your API changes, you can simply regenerate the types to get up-to-date TypeScript types. This ensures that your API client always matches the current state of your API.

5. **Reduced Boilerplate**: Without a tool like [`openapi-typescript`](https://github.com/drwpow/openapi-typescript), you would have to manually write interfaces or types for your API responses and requests. This can be time-consuming and error-prone. With [`openapi-typescript`](https://github.com/drwpow/openapi-typescript), this boilerplate is automatically generated for you.


## Best practices

Here are some best practices for managing API calls in the project:

1. **Modularize API Endpoints**: Keep endpoints for each API scope in separate files. Combine them in a global [`api/endpoints.tsx`](https://github.com/TheoD02/battery-stats-tracker/blob/feat/base/app/assets/src/api/endpoints.tsx) file.

2. **Centralize API Client Configuration**: Manage all API client configurations in the [`api/client.tsx`](https://github.com/TheoD02/battery-stats-tracker/blob/feat/base/app/assets/src/api/client.tsx) file.

3. **Use Query Keys**: Declare keys for queries for each scope in separate files and combine them in a global [`api/keys.tsx`](https://github.com/TheoD02/battery-stats-tracker/blob/feat/base/app/assets/src/api/keys.tsx) file.

4. **Use Custom Hooks for Mutations and Queries**: Define mutations and queries using custom hooks that use the `useMutation` and `useQuery` hooks from [`@tanstack/react-query`](https://tanstack.com/query/latest/docs/framework/react/overview).

5. **Generate Types from OpenAPI Specification**: Use the [`openapi-typescript`](https://github.com/drwpow/openapi-typescript) library to generate TypeScript types from the OpenAPI specification.

6. **Handle Errors Globally**: Use a middleware, `throwOnError`, to handle HTTP errors globally.

7. **Use Promises for Asynchronous Operations**: Handle asynchronous API calls using promises or async/await syntax.

8. **Use Environment Variables for API URLs**: Store the base URL for the API in an environment variable.

9. **Document API Calls**: Document each API call with comments that explain its purpose, parameters, and return values.

## What to avoid

Here are some common mistakes to avoid when managing API calls:

1. **Not Handling Errors Properly**: API calls can fail for various reasons, such as network issues, server errors, or invalid request parameters. It's important to handle these errors properly in your code to prevent the application from crashing and to provide a good user experience.

2. **Ignoring HTTP Status Codes**: Each HTTP response comes with a status code that indicates the result of the request. Ignoring these status codes can lead to incorrect handling of the responses.

3. **Hardcoding API URLs**: Hardcoding API URLs in your code can make it difficult to switch between different environments (like development, staging, and production). It's better to store these URLs in environment variables.

4. **Not Modularizing API Calls**: Keeping all API calls in one file can make your code messy and hard to maintain. It's better to modularize your API calls by creating separate functions or hooks for each endpoint.

5. **Not Using a Centralized API Client**: Using a centralized API client can make it easier to manage common tasks like setting headers, handling errors, and configuring the base URL.

6. **Not Documenting API Calls**: Each API call should be documented with comments that explain what the call does, what parameters it accepts, and what it returns. This can be especially helpful for other developers who are working on the project.

7. **Not Using Types or Interfaces for API Responses**: If you're using a statically typed language like TypeScript, it's a good practice to define types or interfaces for your API responses. This can help catch errors at compile time and provide autocompletion in your IDE.

8. **Not Regenerating Types When API Changes**: If you're using a tool like [`openapi-typescript`](https://github.com/drwpow/openapi-typescript) to generate types from your API specification, it's important to regenerate these types whenever the API changes to ensure they are up-to-date.