const express = require('express');
const router = express.Router();
const bcrypt = require('bcryptjs');
const { pool } = require('../config/db');
const { isAuthenticated } = require('../middleware/auth');

// Admin login page
router.get('/login', (req, res) => {
    if (req.session.isAuthenticated) {
        return res.redirect('/admin/dashboard');
    }
    res.render('admin/login', { error: null });
});

// Admin login handler
router.post('/login', async (req, res) => {
    const { email, password } = req.body;
    
    try {
        const result = await pool.query('SELECT * FROM admins WHERE email = $1', [email]);
        const admin = result.rows[0];
        
        if (!admin) {
            return res.render('admin/login', { error: 'Invalid credentials' });
        }
        
        const isValidPassword = await bcrypt.compare(password, admin.password_hash);
        
        if (!isValidPassword) {
            return res.render('admin/login', { error: 'Invalid credentials' });
        }
        
        req.session.isAuthenticated = true;
        req.session.adminEmail = admin.email;
        res.redirect('/admin/dashboard');
    } catch (error) {
        console.error('Login error:', error);
        res.render('admin/login', { error: 'An error occurred during login' });
    }
});

// Admin logout
router.get('/logout', (req, res) => {
    req.session.destroy((err) => {
        if (err) {
            console.error('Logout error:', err);
        }
        res.redirect('/admin/login');
    });
});

// Admin dashboard
router.get('/dashboard', isAuthenticated, async (req, res) => {
    try {
        // Get pending and approved companies separately
        const pendingResult = await pool.query(
            'SELECT * FROM companies WHERE approved = false ORDER BY created_at DESC'
        );
        const approvedResult = await pool.query(
            'SELECT * FROM companies WHERE approved = true ORDER BY created_at DESC'
        );
        
        res.render('admin/dashboard', { 
            pendingCompanies: pendingResult.rows,
            approvedCompanies: approvedResult.rows,
            error: null,
            success: req.query.success
        });
    } catch (error) {
        console.error('Dashboard error:', error);
        res.render('admin/dashboard', { 
            pendingCompanies: [],
            approvedCompanies: [],
            error: 'Error fetching companies',
            success: null
        });
    }
});

// Approve company
router.post('/companies/:id/approve', isAuthenticated, async (req, res) => {
    const { id } = req.params;
    
    try {
        await pool.query(
            'UPDATE companies SET approved = true WHERE id = $1',
            [id]
        );
        res.redirect('/admin/dashboard?success=Company approved successfully');
    } catch (error) {
        console.error('Approval error:', error);
        res.redirect('/admin/dashboard?error=Error approving company');
    }
});

// Delete company
router.post('/companies/:id/delete', isAuthenticated, async (req, res) => {
    const { id } = req.params;
    
    try {
        await pool.query('DELETE FROM companies WHERE id = $1', [id]);
        res.redirect('/admin/dashboard?success=Company deleted successfully');
    } catch (error) {
        console.error('Delete error:', error);
        res.redirect('/admin/dashboard?error=Error deleting company');
    }
});

// Edit company page
router.get('/edit/:id', isAuthenticated, async (req, res) => {
    const { id } = req.params;
    
    try {
        const result = await pool.query(
            'SELECT * FROM companies WHERE id = $1',
            [id]
        );
        
        if (result.rows.length === 0) {
            return res.redirect('/admin/dashboard?error=Company not found');
        }
        
        res.render('admin/edit', { 
            company: result.rows[0],
            error: null
        });
    } catch (error) {
        console.error('Edit page error:', error);
        res.redirect('/admin/dashboard?error=Error loading company data');
    }
});

// Update company
router.post('/edit/:id', isAuthenticated, async (req, res) => {
    const { id } = req.params;
    const { name, oneliner, description, website, location, sector, contact_email } = req.body;
    
    try {
        await pool.query(
            `UPDATE companies 
             SET name = $1, oneliner = $2, description = $3, website = $4, 
                 location = $5, sector = $6, contact_email = $7
             WHERE id = $8`,
            [name, oneliner, description, website, location, sector, contact_email, id]
        );
        
        res.redirect('/admin/dashboard?success=Company updated successfully');
    } catch (error) {
        console.error('Update error:', error);
        const result = await pool.query('SELECT * FROM companies WHERE id = $1', [id]);
        res.render('admin/edit', {
            company: result.rows[0],
            error: 'Error updating company'
        });
    }
});

module.exports = router; 