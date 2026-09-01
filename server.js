const express = require('express');
const mysql = require('mysql2');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 10000;

// Static ፋይሎችን እና uploads ፎልደርን ለማስተናገድ
app.use(express.static(__dirname));
app.use('/uploads', express.static(path.join(__dirname, 'uploads')));
app.use(express.json());

// MySQL Connection
const db = mysql.createPool({
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME || 'restaurant_db',
    waitForConnections: true,
    connectionLimit: 10
});

// የሜኑ ዳታ ማምጫ API (እነ index.php ላይ በ JS fetch ለማድረግ)
app.get('/api/menu', (req, res) => {
    db.query('SELECT * FROM menu_items', (err, results) => {
        if (err) return res.status(500).json({ error: err.message });
        res.json(results);
    });
});

app.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
});
