const { Pool } = require('pg');
const fs = require('fs');
const path = require('path');

const pool = new Pool({
    connectionString: process.env.DATABASE_URL,
    ssl: process.env.NODE_ENV === 'production' ? { rejectUnauthorized: false } : false
});

// Function to initialize the database
const initializeDatabase = async () => {
    try {
        // Read the SQL file
        const sqlFile = path.join(__dirname, 'init.sql');
        const sqlQuery = fs.readFileSync(sqlFile, 'utf8');
        
        // Execute the SQL query
        await pool.query(sqlQuery);
        console.log('Database initialized successfully');
    } catch (error) {
        console.error('Error initializing database:', error);
        // Don't throw the error - let the app continue even if table exists
    }
};

// Initialize database when this module is imported
initializeDatabase();

module.exports = {
    query: (text, params) => pool.query(text, params),
    pool,
    initializeDatabase // Export this in case we need to reinitialize
}; 