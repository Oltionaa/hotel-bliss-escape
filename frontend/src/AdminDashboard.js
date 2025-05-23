import axios from 'axios';
import { useEffect, useState, useCallback } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Table, Alert } from 'react-bootstrap';
import { Bar } from 'react-chartjs-2';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js';

// Register Chart.js components
ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const AdminDashboard = () => {
  const [dashboardData, setDashboardData] = useState(null);
  const [error, setError] = useState(null);
  const navigate = useNavigate();

  const fetchDashboardData = useCallback(async () => {
    const token = localStorage.getItem('token');
    const userType = localStorage.getItem('userType')?.trim().toLowerCase();
    console.log('AdminDashboard: token:', token, 'userType:', userType);

    if (!token || userType !== 'admin') {
      console.log('Redirecting to login: No token or wrong userType');
      localStorage.removeItem('token');
      localStorage.removeItem('user_id');
      localStorage.removeItem('userType');
      navigate('/login', { replace: true });
      return;
    }

    try {
      const response = await axios.get('http://localhost:8000/api/admin/dashboard', {
        headers: { Authorization: `Bearer ${token}` },
      });
      console.log('Dashboard Data:', response.data);
      setDashboardData(response.data);
      setError(null);
    } catch (err) {
      console.error('Gabim gjatë marrjes së të dhënave të panelit:', err.response?.data || err.message);
      if (err.response?.status === 401 || err.response?.status === 403) {
        setError('Sesioni juaj ka skaduar. Ju lutemi hyni përsëri.');
        localStorage.removeItem('token');
        localStorage.removeItem('user_id');
        localStorage.removeItem('userType');
        navigate('/login', { replace: true });
      } else {
        setError('Gabim në server. Ju lutemi provoni përsëri më vonë.');
      }
    }
  }, [navigate]);

  useEffect(() => {
    fetchDashboardData();
  }, [fetchDashboardData]);

  const chartData = {
    labels: ['Admin', 'Recepsionistë', 'Pastrues'],
    datasets: [
      {
        label: 'Numri i Përdoruesve',
        data: dashboardData
          ? [dashboardData.stats.admins, dashboardData.stats.receptionists, dashboardData.stats.cleaners]
          : [0, 0, 0],
        backgroundColor: ['#4CAF50', '#2196F3', '#FF9800'],
        borderColor: ['#388E3C', '#1976D2', '#F57C00'],
        borderWidth: 1,
      },
    ],
  };

  const chartOptions = {
    responsive: true,
    scales: {
      y: {
        beginAtZero: true,
        title: {
          display: true,
          text: 'Numri i Përdoruesve',
        },
      },
      x: {
        title: {
          display: true,
          text: 'Rolet',
        },
      },
    },
    plugins: {
      legend: {
        display: true,
      },
      title: {
        display: true,
        text: 'Shpërndarja e Përdoruesve sipas Roleve',
      },
      annotation: {
        annotations: {
          activeUsers: {
            type: 'label',
            content: dashboardData ? `Përdorues Aktivë: ${dashboardData.stats.active_users}` : 'Përdorues Aktivë: 0',
            position: 'top',
            backgroundColor: 'rgba(0, 0, 0, 0.7)',
            color: '#FFFFFF',
            xAdjust: 0,
            yAdjust: -20,
          },
        },
      },
    },
  };

  // Map action types to translated messages
  const getActionTranslation = (action) => {
    const actionMap = {
      'cleaned room': 'Pastroi dhomën',
      'uncleaned room': 'Dhoma e papastër',
      'created reservation': 'Krijoi rezervim',
      'updated reservation': 'Përditësoi rezervimin',
      'cancelled reservation': 'Anuloi rezervimin',
      'processed payment': 'Regjistroi pagesën',
    };
    return actionMap[action] || 'Veprim i panjohur';
  };

  return (
    <div className="container py-5">
      <h1>Paneli i Administratorit</h1>
      {error && <Alert variant="danger">{error}</Alert>}
      {dashboardData ? (
        <div className="row">
          <div className="col-md-6">
            <div className="card mb-4 shadow-sm">
              <div className="card-body">
                <h5 className="card-title">Përdoruesit</h5>
                <p className="card-text">Numri total: {dashboardData.stats.total_users}</p>
                <p className="card-text">Admin: {dashboardData.stats.admins}</p>
                <p className="card-text">Recepsionistë: {dashboardData.stats.receptionists}</p>
                <p className="card-text">Pastrues: {dashboardData.stats.cleaners}</p>
                <p className="card-text">Aktivë: {dashboardData.stats.active_users}</p>
                <Link to="/users" className="btn btn-primary">
                  Shiko Përdoruesit
                </Link>
              </div>
            </div>
          </div>
          <div className="col-md-6">
            <div className="card mb-4 shadow-sm">
              <div className="card-body">
                <h5 className="card-title">Grafiku i Përdoruesve</h5>
                <Bar data={chartData} options={chartOptions} />
              </div>
            </div>
            <div className="card mb-4 shadow-sm">
              <div className="card-body">
                <h5 className="card-title">Aktivitetet e Fundit</h5>
                {dashboardData.activities && dashboardData.activities.length > 0 ? (
                  <Table striped bordered hover responsive>
                    <thead>
                      <tr>
                        <th>Përdoruesi</th>
                        <th>Roli</th>
                        <th>Veprimi</th>
                        <th>Objekti</th>
                        <th>Data</th>
                      </tr>
                    </thead>
                    <tbody>
                      {dashboardData.activities.map((activity) => (
                        <tr key={activity.id}>
                          <td>{activity.user_name || 'I panjohur'}</td>
                          <td>
                            {activity.user_role === 'cleaner'
                              ? 'Pastrues'
                              : activity.user_role === 'receptionist'
                              ? 'Recepsionist'
                              : activity.user_role === 'admin'
                              ? 'Admin'
                              : activity.user_role || 'N/A'}
                          </td>
                          <td>{getActionTranslation(activity.action)}</td>
                          <td>{activity.target || 'N/A'}</td>
                          <td>{new Date(activity.created_at).toLocaleString('sq-AL')}</td>
                        </tr>
                      ))}
                    </tbody>
                  </Table>
                ) : (
                  <p className="text-muted">Nuk ka aktivitete të regjistruara.</p>
                )}
              </div>
            </div>
          </div>
        </div>
      ) : (
        !error && <p>Duke u ngarkuar...</p>
      )}
    </div>
  );
};

export default AdminDashboard;