import React, { useState } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import 'bootstrap/dist/css/bootstrap.min.css';

const Login = () => {
  const [isLogin, setIsLogin] = useState(true);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const navigate = useNavigate();

  const handleLogin = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    if (!/\S+@\S+\.\S+/.test(email)) {
      setError('Email është i pavlefshëm.');
      setLoading(false);
      return;
    }
    if (password.length < 6) {
      setError('Fjalëkalimi duhet të jetë të paktën 6 karaktere.');
      setLoading(false);
      return;
    }

    try {
      const response = await axios.post('http://localhost:8000/api/login', { email, password });
      console.log('Login response:', response.data); // Debug: Shiko përgjigjen
      localStorage.setItem('token', response.data.token); // Ruaj token-in
      localStorage.setItem('user_id', response.data.user.id); // Ruaj user_id me çelësin e saktë
      navigate('/dashboard'); // Shko te dashboard-i pas login-it
    } catch (error) {
      setError(error.response?.data?.message || 'Identifikimi dështoi.');
      console.error('Login error:', error.response?.data || error);
    } finally {
      setLoading(false);
    }
  };

  const handleRegister = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    if (!name.trim()) {
      setError('Emri është i nevojshëm.');
      setLoading(false);
      return;
    }
    if (!/\S+@\S+\.\S+/.test(email)) {
      setError('Email është i pavlefshëm.');
      setLoading(false);
      return;
    }
    if (password.length < 6) {
      setError('Fjalëkalimi duhet të jetë të paktën 6 karaktere.');
      setLoading(false);
      return;
    }
    if (password !== confirmPassword) {
      setError('Fjalëkalimi dhe konfirmimi i fjalëkalimit nuk përputhen.');
      setLoading(false);
      return;
    }

    try {
      const response = await axios.post('http://localhost:8000/api/register', { 
        name, 
        email, 
        password, 
        password_confirmation: confirmPassword 
      });
      console.log('Register response:', response.data);
      localStorage.setItem('token', response.data.token); // Ruaj token-in pas regjistrimit
      localStorage.setItem('user_id', response.data.user.id); // Ruaj user_id
      setIsLogin(true);
      resetFields();
      navigate('/'); // Shko te dashboard-i pas regjistrimit
    } catch (error) {
      setError(error.response?.data?.message || 'Regjistrimi dështoi.');
      console.error('Register error:', error.response?.data || error);
    } finally {
      setLoading(false);
    }
  };

  const resetFields = () => {
    setName('');
    setEmail('');
    setPassword('');
    setConfirmPassword('');
  };

  return (
    <div className="d-flex align-items-center justify-content-center vh-100 bg-light">
      <div className="card shadow p-4" style={{ width: '100%', maxWidth: '450px' }}>
        <h2 className="text-center mb-4">{isLogin ? 'Identifikohu' : 'Regjistrohu'}</h2>
        <form onSubmit={isLogin ? handleLogin : handleRegister}>
          {!isLogin && (
            <div className="mb-3">
              <label className="form-label">Emri i Plotë</label>
              <input
                type="text"
                className="form-control"
                placeholder="Shkruani emrin tuaj"
                value={name}
                onChange={(e) => setName(e.target.value)}
                required
              />
            </div>
          )}

          <div className="mb-3">
            <label className="form-label">Email</label>
            <input
              type="email"
              className="form-control"
              placeholder="Shkruani email-in tuaj"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
            />
          </div>

          <div className="mb-3">
            <label className="form-label">Fjalëkalimi</label>
            <input
              type="password"
              className="form-control"
              placeholder="Shkruani fjalëkalimin tuaj"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
          </div>

          {!isLogin && (
            <div className="mb-3">
              <label className="form-label">Konfirmo Fjalëkalimin</label>
              <input
                type="password"
                className="form-control"
                placeholder="Konfirmo fjalëkalimin tuaj"
                value={confirmPassword}
                onChange={(e) => setConfirmPassword(e.target.value)}
                required
              />
            </div>
          )}

          {error && <div className="alert alert-danger">{error}</div>}

          <div className="d-grid gap-2">
            <button className="btn btn-dark" disabled={loading}>
              {loading ? 'Duke u ngarkuar...' : isLogin ? 'Identifikohu' : 'Regjistrohu'}
            </button>
          </div>
        </form>

        <div className="text-center mt-3">
          <p>
            {isLogin ? "Nuk keni llogari?" : "Keni tashmë një llogari?"}{' '}
            <button className="btn btn-link p-0" onClick={() => setIsLogin(!isLogin)}>
              {isLogin ? 'Regjistrohu' : 'Identifikohu'}
            </button>
          </p>
        </div>
      </div>
    </div>
  );
};

export default Login;