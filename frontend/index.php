<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Todo App</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 1024px; margin: 0 auto; padding: 0px;">
    <h1 style="text-align: center;">Welcome to the PHP Todo App</h1>
    <p style="text-align: center;">This is the main entry point of the application.</p>
    <p style="text-align: center;">Use the API endpoints to interact with the application.</p>
    <table style="text-align: left; width: 100%; margin: 0 auto; border-collapse: collapse;" border="1">
        <tr>
            <th>Endpoint</th>
            <th>Description</th>
        </tr>
        <tr>
            <td>GET /totos</td>
            <td>List all todo</td>
        </tr>
        <tr>
            <td>POST /totos</td>
            <td>Add a new todo item</td>
        </tr>
        <tr>
            <td>GET /totos/{id}</td>
            <td>Get a specific todo item by ID</td>
        </tr>
        <tr>
            <td>PUT /totos/{id}</td>
            <td>Update a specific todo item by ID</td>
        </tr>
        <tr>
            <td>DELETE /totos/{id}</td>
            <td>Delete a specific todo item by ID</td>
        </tr>
        <tr>
            <td>GET /users</td>
            <td>List all users</td>
        </tr>
        <tr>
            <td>POST /users/register</td>
            <td>Register a new user</td>
        </tr>
        <tr>
            <td>GET /users/{user_id}</td>
            <td>Get a specific user by USER_ID</td>
        </tr>
        <tr>
            <td>PUT /users/{user_id}</td>
            <td>Update a specific user by USER_ID</td>
        </tr>
        <tr>
            <td>DELETE /users/{user_id}</td>
            <td>Delete a specific user by USER_ID</td>
        </tr>
        <tr>
            <td>DELETE /auth/login</td>
            <td>Login user validate and create token</td>
        </tr>
        <tr>
            <td>DELETE /auth/validate</td>
            <td>Validate token </td>
        </tr>
    </table>
    <hr />
    <div >
        <table style="text-align: left; width: 100%; margin: 0 auto; border-collapse: collapse;" border="1">
            <tr>
                <th>Feature</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>Routing</td>
                <td>Implemented</td>
            </tr>
            <tr>
                <td>Controllers</td>
                <td>Implemented</td>
            </tr>
            <tr>
                <td>Middleware (CORS, Logging, Rate Limiting)</td>
                <td>Implemented</td>
            </tr>
            <tr>
                <td>JWT Authentication (Commented Out)</td>
                <td>Partially Implemented</td>
            </tr>
            <tr>
                <td>Input Validation</td>
                <td>Implemented</td>
            </tr>
            <tr>
                <td>Error Handling</td>
                <td>Implemented</td>
            </tr>
            <tr>
                <td>Database Interaction (Using PDO)</td>
                <td>Implemented</td>
            </tr>
            <tr>    
                <td>User Management</td>
                <td>Implemented</td>
            </tr>
            <tr>    
                <td>Todos Management</td>
                <td>Implemented</td>
            </tr>            
        </table>
    </div>
    <hr />
    <p style="text-align: center;">
        <a href="https://github.com/php-todo-app/php-todo-app" target="_blank"
           style="text-decoration: none; color: #007bff; font-weight: bold;">
           GitHub Repository
        </a>
    </p>
    <hr />

    <footer style="text-align: center;">
        <p>&copy; 2025 PHP Todo App</p>
    </footer>
</body>
</html>