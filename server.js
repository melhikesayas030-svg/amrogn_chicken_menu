const express = require('express');
const mysql = require('mysql2');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 10000;

// Middleware for static files & JSON parsing
app.use(express.static(path.join(__dirname)));
app.use('/uploads', express.static(path.join(__dirname, 'uploads')));
app.use(express.json());

// 1. ዋናውን index.html ገጽ ለማሳየት
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'index.html'));
});

// 2. MySQL Connection (Aiven MySQL Configuration with SSL support)
const db = mysql.createPool({
    host: process.env.DB_HOST,
    port: process.env.DB_PORT || 22746,
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_NAME,
    waitForConnections: true,
    connectionLimit: 10,
    ssl: {
        rejectUnauthorized: false // Aiven MySQL SSL Connection ለማድረግ አስፈላጊ ነው
    }
});

// 3. API Route for Menu Items
app.get('/api/menu', (req, res) => {
    db.query('SELECT * FROM menu_items', (err, results) => {
        if (err) {
            console.error('Database Error:', err.message);
            return res.status(500).json({ error: 'Database connection or query failed' });
        }
        res.json(results);
    });
});

// Server Listening
app.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
});
