import React, { useState } from 'react';
import axios from 'axios';

const LoginForm = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  const handleSubmit = async e => {
    e.preventDefault();

    if (!/\S+@\S+\.\S+/.test(email)) {
      alert('Email është i pavlefshëm. Ju lutemi jepni një email të saktë.');
      return;
    }

    if (password.length < 6) {
      alert('Password duhet të ketë të paktën 6 karaktere.');
      return;
    }

    try {
      const response = await axios.post('http://localhost:8000/api/login', {
        email,
        password
      });
      console.log('Login successful:', response.data);
    } catch (error) {
      console.error('Login error:', error.response?.data);
    }
  };

  return (
    <div className="container d-flex justify-content-center align-items-center mt-5">
      <div className="card shadow-lg p-4" style={{ maxWidth: '400px', width: '100%' }}>
        <h2 className="text-center mb-4">Login</h2>
        <form onSubmit={handleSubmit}>
          <div className="mb-3">
            <label className="form-label">Email address</label>
            <input 
              type="email" 
              className="form-control" 
              placeholder="Enter your email"
              value={email} 
              onChange={e => setEmail(e.target.value)} 
              required
            />
          </div>
          <div className="mb-3">
            <label className="form-label">Password</label>
            <input 
              type="password" 
              className="form-control" 
              placeholder="Enter your password"
              value={password} 
              onChange={e => setPassword(e.target.value)} 
              required 
            />
          </div>
          <div className="d-grid gap-2">
            <button className="btn btn-primary">Login</button>
          </div>
        </form>
        <div className="text-center mt-3">
          <p>Don't have an account? <a href="C:\xampp\htdocs\hoteli\frontend\src\RegisterForm.jsx">Register</a></p>
        </div>
      </div>
    </div>
  );
};

export default LoginForm;
