import React, { useState } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';

const CreateUser = () => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'user',
    status: 'active',
  });
  const [error, setError] = useState(null);
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      const token = localStorage.getItem('token');
      console.log('Token:', token); // Log token-in për diagnostikim
      if (!token) {
        setError('Ju lutemi hyni për të krijuar një përdorues.');
        return;
      }

      const response = await axios.post('http://localhost:8000/api/admin/users', formData, {
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      console.log('Response:', response.data); // Log përgjigjen
      navigate('/users');
    } catch (err) {
      console.error('Gabim gjatë krijimit të përdoruesit:', err);
      console.error('Status:', err.response?.status);
      console.error('Data:', err.response?.data);
      if (err.response?.status === 401) {
        setError('Sesioni juaj ka skaduar. Ju lutemi hyni përsëri.');
      } else if (err.response?.status === 422) {
        setError(err.response.data.errors || 'Të dhënat e pavlefshme.');
      } else {
        setError('Gabim gjatë krijimit të përdoruesit. Provoni përsëri.');
      }
    }
  };

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  return (
    <div className="container py-5">
      <h1>Krijo Përdorues</h1>
      {error && <div className="alert alert-danger">{JSON.stringify(error)}</div>}
      <form onSubmit={handleSubmit}>
        <div className="mb-3">
          <label className="form-label">Emri</label>
          <input
            type="text"
            name="name"
            className="form-control"
            value={formData.name}
            onChange={handleChange}
            required
          />
        </div>
        <div className="mb-3">
          <label className="form-label">Email</label>
          <input
            type="email"
            name="email"
            className="form-control"
            value={formData.email}
            onChange={handleChange}
            required
          />
        </div>
        <div className="mb-3">
          <label className="form-label">Fjalëkalimi</label>
          <input
            type="password"
            name="password"
            className="form-control"
            value={formData.password}
            onChange={handleChange}
            required
          />
        </div>
        <div className="mb-3">
          <label className="form-label">Konfirmo Fjalëkalimin</label>
          <input
            type="password"
            name="password_confirmation"
            className="form-control"
            value={formData.password_confirmation}
            onChange={handleChange}
            required
          />
        </div>
        <div className="mb-3">
          <label className="form-label">Roli</label>
          <select
            name="role"
            className="form-select"
            value={formData.role}
            onChange={handleChange}
            required
          >
            <option value="admin">Admin</option>
            <option value="receptionist">Recepsionist</option>
            <option value="cleaner">Cleaner</option>
            <option value="user">Përdorues</option>
          </select>
        </div>
        <div className="mb-3">
          <label className="form-label">Statusi</label>
          <select
            name="status"
            className="form-select"
            value={formData.status}
            onChange={handleChange}
          >
            <option value="active">Aktiv</option>
            <option value="inactive">Joaktiv</option>
          </select>
        </div>
        <button type="submit" className="btn btn-primary">Krijo</button>
      </form>
    </div>
  );
};

export default CreateUser;