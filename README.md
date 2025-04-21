# BioTech Wales

A directory website showcasing biotech companies based in Wales. Built with Node.js, Express, Bootstrap, and PostgreSQL.

## Features

- Browse Welsh biotech companies
- Filter companies by sector
- Submit new company listings
- Responsive design
- Admin approval system for new submissions

## Setup

1. Clone the repository:
```bash
git clone https://github.com/yourusername/biotechwales.git
cd biotechwales
```

2. Install dependencies:
```bash
npm install
```

3. Set up PostgreSQL Database on Render:
   - Create a new PostgreSQL database on [Render](https://render.com)
   - Note down your database connection URL

4. Create a `.env` file in the root directory:
```
PORT=3000
DATABASE_URL=your_postgresql_database_url
NODE_ENV=development
```

5. Initialize the database:
   - Connect to your PostgreSQL database
   - Run the SQL commands from `config/init.sql`

6. Start the development server:
```bash
npm run dev
```

The application will be available at `http://localhost:3000`

## Deployment to Render

1. Push your code to a Git repository (GitHub, GitLab, etc.)

2. On Render:
   - Create a new Web Service
   - Connect your repository
   - Set the following:
     - Environment: Node
     - Build Command: `npm install`
     - Start Command: `npm start`
   - Add environment variables:
     - `DATABASE_URL`: Your Render PostgreSQL database URL
     - `NODE_ENV`: production

3. Deploy your application

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License.