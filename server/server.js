import express from 'express';
import cors from 'cors';
import axios from 'axios';
import dotenv from 'dotenv';
dotenv.config();

const app = express();
const PORT = process.env.PORT || 9000;
const SECRET_KEY = process.env.SECRET_KEY;

// Middleware
app.use(cors());
app.use(express.json());

// POST endpoint to forward registration data
app.post('/api/register', async (req, res) => {
  try {
    const { source, college_id, campus, course, name, email, mobile, field_state_new}=req?.body || {};
    if( !source || !college_id || !campus || !course || !name || !email || !mobile || !field_state_new){
      return res.status(400).json({
        status: 'Error',
        message: 'Missing required fields'
      });
    }
    const payload = {
        secret_key: SECRET_KEY,
      source,
      college_id,
      campus,
      course,
      name,
      email,
      mobile,
      field_state_new
    };

    // Add optional fields if present
    if (req?.body?.medium) {
      payload.medium = req.body.medium;
    }
    if (req?.body?.campaign) {
      payload.campaign = req.body.campaign;
    }

    // Forward request to external API
    const response = await axios.post(
      'https://api.in8.nopaperforms.com/dataporting/6205/stealth',
      payload,
      {
        headers: {
          'Content-Type': 'application/json'
        }
      }
    );

    // Return the response from external API
    res.status(response?.status).json(response?.data);
  } catch (error) {
    console.error('Error forwarding request:', error?.message);

    // Handle axios errors
    if (error.response) {
      // The request was made and the server responded with a status code
      // that falls out of the range of 2xx
      res.status(error.response.status).json(error.response.data);
    } else if (error.request) {
      // The request was made but no response was received
      res.status(500).json({
        status: 'Error',
        message: 'No response received from external API'
      });
    } else {
      // Something happened in setting up the request that triggered an Error
      res.status(500).json({
        status: 'Error',
        message: error.message
      });
    }
  }
});


app.get('/', (req, res) => {
  res.json({ status: 'OK', message: 'Server is running' });
});

// Start server
app.listen(PORT, () => {
  console.log(`Server is running on http://localhost:${PORT}`);
});
