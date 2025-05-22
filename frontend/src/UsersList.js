import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import { Link, useNavigate } from 'react-router-dom';

const UsersList = () => {
  const [users, setUsers] = useState([]);
  const [meta, setMeta] = useState({});
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [search, setSearch] = useState('');
  const [role, setRole] = useState('');
  const [page, setPage] = useState(1);
  const navigate = useNavigate();

  const fetchUsers = useCallback(async () => {
    try {
      setLoading(true);
      const token = localStorage.getItem('token');
      const userType = localStorage.getItem('userType')?.trim().toLowerCase();
      console.log('fetchUsers: Token:', token, 'UserType:', userType);

      if (!token || userType !== 'admin') {
        setError('Ju lutemi hyni si admin për të parë përdoruesit.');
        localStorage.removeItem('token');
        localStorage.removeItem('user_id');
        localStorage.removeItem('userType');
        navigate('/login', { replace: true });
        return;
      }

      const params = { page };
      if (search) params.search = search;
      if (role) params.role = role;
      console.log('fetchUsers: Params:', params);

      await axios.get('http://localhost:8000/sanctum/csrf-cookie');
      const response = await axios.get('http://localhost:8000/api/admin/users', {
        headers: {
          Authorization: `Bearer ${token}`,
        },
        params,
      });

      console.log('fetchUsers: Response:', response.data);
      setUsers(response.data.data?.data || []);
      setMeta(response.data.data?.meta || {});
      setError(null);
    } catch (err) {
      console.error('fetchUsers: Error:', err.response?.data || err);
      console.error('fetchUsers: Status:', err.response?.status);
      console.error('fetchUsers: Data:', err.response?.data);
      if (err.response?.status === 401) {
        setError('Sesioni juaj ka skaduar. Ju lutemi hyni përsëri.');
        localStorage.removeItem('token');
        localStorage.removeItem('user_id');
        localStorage.removeItem('userType');
        navigate('/login', { replace: true });
      } else if (err.response?.status === 403) {
        setError('Nuk keni autorizim për të parë përdoruesit.');
        localStorage.removeItem('token');
        localStorage.removeItem('user_id');
        localStorage.removeItem('userType');
        navigate('/login', { replace: true });
      } else {
        setError(err.response?.data?.error || 'Gabim gjatë marrjes së përdoruesve.');
      }
    } finally {
      setLoading(false);
    }
  }, [search, role, page, navigate]);

  useEffect(() => {
    fetchUsers();
  }, [fetchUsers]);

  const handleDelete = async (id) => {
    if (!window.confirm('Jeni i sigurt që doni të fshini këtë përdorues?')) return;

    try {
      const token = localStorage.getItem('token');
      console.log('handleDelete: Sending DELETE with token:', token, 'for user ID:', id);

      await axios.get('http://localhost:8000/sanctum/csrf-cookie');
      const response = await axios.delete(`http://localhost:8000/api/admin/users/${id}`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });

      console.log('handleDelete: Response:', response.data);
      setUsers(users.filter((user) => user.id !== id));
      setError(null);
    } catch (err) {
      console.error('handleDelete: Error:', err.response?.data || err);
      console.error('handleDelete: Status:', err.response?.status);
      console.error('handleDelete: Data:', err.response?.data);
      setError(
        err.response?.data?.error ||
        err.response?.data?.message ||
        'Gabim gjatë fshirjes së përdoruesit.'
      );
      if (err.response?.status === 401 || err.response?.status === 403) {
        localStorage.removeItem('token');
        localStorage.removeItem('user_id');
        localStorage.removeItem('userType');
        navigate('/login', { replace: true });
      }
    }
  };

  if (loading) return <div className="container py-5">Duke ngarkuar...</div>;
  if (error) return <div className="container py-5 text-danger">{error}</div>;

  return (
    <div className="container py-5">
      <h1>Lista e Përdoruesve</h1>
      <Link to="/users/create" className="btn btn-primary mb-3">Shto Përdorues</Link>
      <div className="row mb-3">
        <div className="col-md-6">
          <input
            type="text"
            className="form-control"
            placeholder="Kërko..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>
        <div className="col-md-3">
          <select
            className="form-select"
            value={role}
            onChange={(e) => setRole(e.target.value)}
          >
            <option value="">Të gjitha rolet</option>
            <option value="admin">Admin</option>
            <option value="receptionist">Recepsionist</option>
            <option value="cleaner">Cleaner</option>
            <option value="user">User</option>
          </select>
        </div>
      </div>
      <table className="table table-striped">
        <thead>
          <tr>
            <th>ID</th>
            <th>Emri</th>
            <th>Email</th>
            <th>Roli</th>
            <th>Statusi</th>
            <th>Veprime</th>
          </tr>
        </thead>
        <tbody>
          {users.map((user) => (
            <tr key={user.id}>
              <td>{user.id}</td>
              <td>{user.name}</td>
              <td>{user.email}</td>
              <td>{user.role}</td>
              <td>{user.status === 'active' ? 'Aktiv' : 'Joaktiv'}</td>
              <td>
                <Link to={`/users/edit/${user.id}`} className="btn btn-sm btn-primary">
                  Edito
                </Link>
                <button
                  onClick={() => handleDelete(user.id)}
                  className="btn btn-sm btn-danger ms-2"
                >
                  Fshi
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
      {meta.last_page > 1 && (
        <div className="d-flex justify-content-between mt-3">
          <button
            className="btn btn-secondary"
            onClick={() => setPage(page - 1)}
            disabled={page === 1}
          >
            Para
          </button>
          <span>
            Faqja {meta.current_page} nga {meta.last_page}
          </span>
          <button
            className="btn btn-secondary"
            onClick={() => setPage(page + 1)}
            disabled={page === meta.last_page}
          >
            Tjetër
          </button>
        </div>
      )}
    </div>
  );
};

export default UsersList;