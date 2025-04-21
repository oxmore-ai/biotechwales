require('dotenv').config();
const express = require('express');
const bodyParser = require('body-parser');
const path = require('path');
const db = require('./config/db');

const app = express();
const port = process.env.PORT || 3000;

// Middleware
app.use(bodyParser.urlencoded({ extended: true }));
app.use(bodyParser.json());
app.use(express.static('public'));
app.set('view engine', 'ejs');

// Routes
app.get('/', async (req, res) => {
    try {
        const result = await db.query(
            'SELECT * FROM companies WHERE approved = true ORDER BY name',
            []
        );
        
        res.render('index', { companies: result.rows });
    } catch (error) {
        console.error('Error:', error);
        res.render('index', { companies: [], error: 'Failed to fetch companies' });
    }
});

app.get('/submit', (req, res) => {
    res.render('submit');
});

app.post('/submit', async (req, res) => {
    try {
        const { name, description, website, location, sector, contact_email } = req.body;
        
        await db.query(
            `INSERT INTO companies 
            (name, description, website, location, sector, contact_email, approved) 
            VALUES ($1, $2, $3, $4, $5, $6, $7)`,
            [name, description, website, location, sector, contact_email, false]
        );

        res.redirect('/?success=true');
    } catch (error) {
        console.error('Error:', error);
        res.redirect('/submit?error=true');
    }
});

// Error handling middleware
app.use((err, req, res, next) => {
    console.error(err.stack);
    res.status(500).render('error', { error: 'Something broke!' });
});

// Database connection test
db.pool.connect()
    .then(() => console.log('Connected to PostgreSQL database'))
    .catch(err => console.error('Database connection error:', err.stack));

app.listen(port, () => {
    console.log(`Server is running on port ${port}`);
}); 