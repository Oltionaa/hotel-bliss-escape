import React, { useState } from 'react';
import axios from 'axios';

const RegisterForm = () => {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');

  const handleSubmit = async e => {
    e.preventDefault();

    // Validime të thjeshta
    if (!name.trim()) {
      alert('Ju lutemi shkruani emrin tuaj.');
      return;
    }

    if (!/\S+@\S+\.\S+/.test(email)) {
      alert('Email është i pavlefshëm.');
      return;
    }

    if (password.length < 6) {
      alert('Password duhet të ketë të paktën 6 karaktere.');
      return;
    }

    if (password !== confirmPassword) {
      alert('Fjalëkalimet nuk përputhen.');
      return;
    }

    try {
      const response = await axios.post('http://localhost:8000/api/register', {
        name,
        email,
        password,
        password_confirmation: confirmPassword
      });
      console.log('Regjistrimi u krye me sukses:', response.data);
    } catch (error) {
      console.error('Gabim gjatë regjistrimit:', error.response?.data);
    }
  };

  return (
    <div className="container d-flex justify-content-center align-items-center mt-5">
      <div className="card shadow-lg p-4" style={{ maxWidth: '450px', width: '100%' }}>
        <h2 className="text-center mb-4">Register</h2>
        <form onSubmit={handleSubmit}>
          <div className="mb-3">
            <label className="form-label">Full Name</label>
            <input
              type="text"
              className="form-control"
              placeholder="Enter your full name"
              value={name}
              onChange={e => setName(e.target.value)}
              required
            />
          </div>

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

          <div className="mb-3">
            <label className="form-label">Confirm Password</label>
            <input
              type="password"
              className="form-control"
              placeholder="Confirm your password"
              value={confirmPassword}
              onChange={e => setConfirmPassword(e.target.value)}
              required
            />
          </div>

          <div className="d-grid gap-2">
            <button className="btn btn-success">Register</button>
          </div>
        </form>

      </div>
    </div>
  );
};

export default RegisterForm;
