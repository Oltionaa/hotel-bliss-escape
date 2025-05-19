import axios from 'axios';
import { useEffect, useState, useCallback } from 'react';
import { Link, useNavigate } from 'react-router-dom'; // Shto useNavigate
import { Bar } from 'react-chartjs-2';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js';

// Regjistro komponentët e Chart.js
ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const AdminDashboard = () => {
  const [dashboardData, setDashboardData] = useState(null);
  const [error, setError] = useState(null);
  const navigate = useNavigate(); // Shto për ridrejtim

  const fetchDashboardData = useCallback(async () => {
    const token = localStorage.getItem('token');
    const userType = localStorage.getItem('userType')?.trim().toLowerCase();
    console.log('AdminDashboard: token:', token, 'userType:', userType);

    // Kontrollo rolin
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
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
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

  // Përgatit të dhënat për grafiku
  const chartData = {
    labels: ['Admin', 'Recepsionistë', 'Cleanerë'],
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

  return (
    <div className="container py-5">
      <h1>Paneli i Administratorit</h1>
      {error && <div className="alert alert-danger">{error}</div>}
      {dashboardData ? (
        <div className="row">
          <div className="col-md-6">
            <div className="card mb-4">
              <div className="card-body">
                <h5 className="card-title">Përdoruesit</h5>
                <p className="card-text">Numri total: {dashboardData.stats.total_users}</p>
                <p className="card-text">Admin: {dashboardData.stats.admins}</p>
                <p className="card-text">Recepsionistë: {dashboardData.stats.receptionists}</p>
                <p className="card-text">Cleanerë: {dashboardData.stats.cleaners}</p>
                <p className="card-text">Aktivë: {dashboardData.stats.active_users}</p>
                <Link to="/users" className="btn btn-primary">
                  Shiko Përdoruesit
                </Link>
              </div>
            </div>
          </div>
          <div className="col-md-6">
            <div className="card mb-4">
              <div className="card-body">
                <h5 className="card-title">Grafiku i Përdoruesve</h5>
                <Bar data={chartData} options={chartOptions} />
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