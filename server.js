require('dotenv').config();
const express = require('express');
const bodyParser = require('body-parser');
const path = require('path');
const multer = require('multer');
const { S3Client, PutObjectCommand } = require('@aws-sdk/client-s3');
const { getSignedUrl } = require('@aws-sdk/s3-request-presigner');
const session = require('express-session');
const pgSession = require('connect-pg-simple')(session);
const db = require('./config/db');
const expressLayouts = require('express-ejs-layouts');
const adminRoutes = require('./routes/admin');

const app = express();
const port = process.env.PORT || 3000;

// S3 configuration
const s3Client = new S3Client({
    region: process.env.AWS_REGION,
    credentials: {
        accessKeyId: process.env.AWS_ACCESS_KEY_ID,
        secretAccessKey: process.env.AWS_SECRET_ACCESS_KEY
    }
});

// Multer configuration for logo uploads
const upload = multer({
    limits: {
        fileSize: 2 * 1024 * 1024, // 2MB limit
    },
    fileFilter: (req, file, cb) => {
        if (file.mimetype === 'image/png' || file.mimetype === 'image/jpeg') {
            cb(null, true);
        } else {
            cb(new Error('Only PNG and JPEG files are allowed'));
        }
    }
});

// Session configuration
app.use(session({
    store: new pgSession({
        pool: db.pool,
        tableName: 'session'
    }),
    secret: process.env.SESSION_SECRET,
    resave: false,
    saveUninitialized: false,
    cookie: {
        maxAge: 30 * 24 * 60 * 60 * 1000 // 30 days
    }
}));

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

// Admin routes
app.use('/admin', adminRoutes);

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

app.post('/submit', upload.single('logo'), async (req, res) => {
    try {
        const { name, oneliner, description, website, location, sector, contact_email } = req.body;
        let logo_url = null;

        if (req.file) {
            const fileKey = `logos/${Date.now()}-${Math.random().toString(36).substring(7)}.${req.file.mimetype.split('/')[1]}`;
            
            const putCommand = new PutObjectCommand({
                Bucket: process.env.AWS_S3_BUCKET,
                Key: fileKey,
                Body: req.file.buffer,
                ContentType: req.file.mimetype
            });

            await s3Client.send(putCommand);
            
            const getCommand = new PutObjectCommand({
                Bucket: process.env.AWS_S3_BUCKET,
                Key: fileKey
            });
            
            logo_url = await getSignedUrl(s3Client, getCommand, { expiresIn: 3600 * 24 * 365 }); // 1 year
        }

        await db.query(
            `INSERT INTO companies 
            (name, oneliner, description, logo_url, website, location, sector, contact_email, approved) 
            VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)`,
            [name, oneliner, description, logo_url, website, location, sector, contact_email, false]
        );

        res.redirect('/?success=true');
    } catch (error) {
        console.error('Error:', error);
        res.render('submit', { 
            error: 'Failed to submit company. Please try again.',
            title: 'Submit Your Company'
        });
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