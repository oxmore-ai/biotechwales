require('dotenv').config();
const express = require('express');
const bodyParser = require('body-parser');
const path = require('path');
const db = require('./config/db');
const expressLayouts = require('express-ejs-layouts');

const app = express();
const port = process.env.PORT || 3000;

// Middleware
app.use(bodyParser.urlencoded({ extended: true }));
app.use(bodyParser.json());
app.use(express.static('public'));

// EJS setup
app.use(expressLayouts);
app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));
app.set('layout extractScripts', true);
app.set('layout extractStyles', true);
app.set('layout', './layouts/main');

// Routes
app.get('/', async (req, res) => {
    try {
        const result = await db.query(
            'SELECT * FROM companies WHERE approved = true ORDER BY name',
            []
        );
        
        res.render('index', { 
            companies: result.rows,
            title: 'Welsh Biotech Companies',
            success: req.query.success === 'true'
        });
    } catch (error) {
        console.error('Error:', error);
        res.render('index', { 
            companies: [], 
            error: 'Failed to fetch companies',
            title: 'Error - Welsh Biotech Companies'
        });
    }
});

app.get('/submit', (req, res) => {
    res.render('submit', { title: 'Submit Your Company' });
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
    res.status(500).render('error', { 
        error: 'Something broke!',
        title: 'Error'
    });
});

// Initialize database and start server
const startServer = async () => {
    try {
        // Test database connection and ensure table exists
        await db.pool.connect();
        console.log('Connected to PostgreSQL database');
        
        // Start the server
        app.listen(port, () => {
            console.log(`Server is running on port ${port}`);
        });
    } catch (err) {
        console.error('Failed to start server:', err);
        process.exit(1);
    }
};

startServer(); 