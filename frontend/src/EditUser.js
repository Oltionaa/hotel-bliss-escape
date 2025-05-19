import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { useParams, useNavigate } from 'react-router-dom';

const EditUser = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    role: '',
    status: 'active',
  });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem('token');
    const userType = localStorage.getItem('userType')?.trim().toLowerCase();
    console.log('EditUser: Token:', token, 'UserType:', userType, 'User ID:', id);

    if (!token || userType !== 'admin') {
      console.log('EditUser: Unauthorized, redirecting to login');
      localStorage.removeItem('token');
      localStorage.removeItem('user_id');
      localStorage.removeItem('userType');
      navigate('/login', { replace: true });
      return;
    }

    const fetchUser = async () => {
      try {
        console.log('EditUser: Fetching user with ID:', id);
        await axios.get('http://localhost:8000/sanctum/csrf-cookie');
        const response = await axios.get(`http://localhost:8000/api/admin/users/${id}`, {
          headers: { Authorization: `Bearer ${token}` },
        });
        console.log('EditUser: Fetch response:', response.data);
        setFormData({
          name: response.data.user.name,
          email: response.data.user.email,
          role: response.data.user.role,
          status: response.data.user.status,
        });
        setError('');
      } catch (err) {
        console.error('EditUser: Error fetching user:', err.response?.data || err);
        console.error('EditUser: Status:', err.response?.status);
        console.error('EditUser: Data:', err.response?.data);
        if (err.response?.status === 401 || err.response?.status === 403) {
          setError('Nuk keni autorizim. Ju lutemi hyni përsëri.');
          localStorage.removeItem('token');
          localStorage.removeItem('user_id');
          localStorage.removeItem('userType');
          navigate('/login', { replace: true });
        } else {
          setError(err.response?.data?.error || 'Gabim gjatë marrjes së përdoruesit.');
        }
      } finally {
        setLoading(false);
      }
    };

    fetchUser();
  }, [id, navigate]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    try {
      const token = localStorage.getItem('token');
      console.log('EditUser: Submitting update for user ID:', id, 'Data:', formData);
      await axios.get('http://localhost:8000/sanctum/csrf-cookie');
      const response = await axios.put(
        `http://localhost:8000/api/admin/users/${id}`,
        formData,
        {
          headers: { Authorization: `Bearer ${token}` },
        }
      );
      console.log('EditUser: Update response:', response.data);
      navigate('/users');
    } catch (err) {
      console.error('EditUser: Error updating user:', err.response?.data || err);
      console.error('EditUser: Status:', err.response?.status);
      console.error('EditUser: Data:', err.response?.data);
      setError(err.response?.data?.error || 'Gabim gjatë përditësimit të përdoruesit.');
    }
  };

  if (loading) return <div className="container py-5">Duke ngarkuar...</div>;
  if (error) return <div className="container py-5 text-danger">{error}</div>;

  return (
    <div className="container py-5">
      <h2>Edito Përdoruesin</h2>
      <form onSubmit={handleSubmit}>
        <div className="mb-3">
          <label htmlFor="name" className="form-label">Emri</label>
          <input
            type="text"
            className="form-control"
            id="name"
            name="name"
            value={formData.name}
            onChange={handleChange}
            required
          />
        </div>
        <div className="mb-3">
          <label htmlFor="email" className="form-label">Email</label>
          <input
            type="email"
            className="form-control"
            id="email"
            name="email"
            value={formData.email}
            onChange={handleChange}
            required
          />
        </div>
        <div className="mb-3">
          <label htmlFor="role" className="form-label">Roli</label>
          <select
            className="form-select"
            id="role"
            name="role"
            value={formData.role}
            onChange={handleChange}
            required
          >
            <option value="admin">Admin</option>
            <option value="receptionist">Recepsionist</option>
            <option value="cleaner">Cleaner</option>
            <option value="user">User</option>
          </select>
        </div>
        <div className="mb-3">
          <label htmlFor="status" className="form-label">Statusi</label>
          <select
            className="form-select"
            id="status"
            name="status"
            value={formData.status}
            onChange={handleChange}
          >
            <option value="active">Aktiv</option>
            <option value="inactive">Joaktiv</option>
          </select>
        </div>
        {error && <div className="alert alert-danger">{error}</div>}
        <button type="submit" className="btn btn-primary">Përditëso</button>
        <button
          type="button"
          className="btn btn-secondary ms-2"
          onClick={() => navigate('/users')}
        >
          Anulo
        </button>
      </form>
    </div>
  );
};

export default EditUser;