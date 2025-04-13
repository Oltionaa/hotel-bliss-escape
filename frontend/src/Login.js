
import React, { useState } from 'react';
import axios from 'axios';
const Login = () => {
  const [isLogin, setIsLogin] = useState(true);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [loading, setLoading] = useState(false);

  // LOGIN
  const handleLogin = async e => {
    e.preventDefault();
    setLoading(true);
    
    if (!/\S+@\S+\.\S+/.test(email)) {
      setLoading(false);
      return;
    }
    if (password.length < 6) {
      setLoading(false);
      return;
    }

    try {
      const response = await axios.post('http://localhost:8000/api/login', { email, password }, { withCredentials: true });
      console.log(response.data);
    } catch (error) {
      console.log(error.response?.data?.message || 'Login dështoi.');
    } finally {
      setLoading(false);
    }
  };

  // REGISTER
  const handleRegister = async e => {
    e.preventDefault();
    setLoading(true);

    if (!name.trim()) {
      setLoading(false);
      return;
    }
    if (!/\S+@\S+\.\S+/.test(email)) {
      setLoading(false);
      return;
    }
    if (password.length < 6) {
      setLoading(false);
      return;
    }
    if (password !== confirmPassword) {
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
      console.log(response.data);
      setIsLogin(true);
      resetFields();
    } catch (error) {
      console.log(error.response?.data?.message || 'Regjistrimi dështoi.');
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
        <h2 className="text-center mb-4">{isLogin ? 'Login' : 'Sign In'}</h2>
        <form onSubmit={isLogin ? handleLogin : handleRegister}>
          {!isLogin && (
            <div className="mb-3">
              <label className="form-label">Full Name</label>
              <input
                type="text"
                className="form-control"
                placeholder="Enter your name"
                value={name}
                onChange={e => setName(e.target.value)}
                required
              />
            </div>
          )}

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

          {!isLogin && (
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
          )}

          <div className="d-grid gap-2">
            <button className={`btn ${isLogin ? 'btn-btn btn-dark' : 'btn-btn btn-dark'}`} disabled={loading}>
              {loading ? 'Loading...' : isLogin ? 'Login' : 'Register'}
            </button>
          </div>
        </form>

        <div className="text-center mt-3">
          <p>
            {isLogin ? "Don't have an account?" : "Already have an account?"}{' '}
            <button className="btn btn-link p-0" onClick={() => setIsLogin(!isLogin)}>
              {isLogin ? 'Sign In' : 'Login'}
            </button>
          </p>
        </div>
      </div>
    </div>
  );
};

export default Login;
