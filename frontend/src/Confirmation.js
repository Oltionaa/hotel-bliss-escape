import React from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import 'bootstrap/dist/css/bootstrap.min.css';

const Confirmation = () => {
  const location = useLocation();
  const navigate = useNavigate();

  const { paymentDetails, roomTitle, checkIn, checkOut } = location.state || {};

  if (!paymentDetails) {
    return (
      <div className="container mt-5">
        <div className="alert alert-danger">
          ❌ No confirmation data available.
        </div>
      </div>
    );
  }

  return (
    <div className="container mt-5" style={{ maxWidth: '600px' }}>
      <div className="card p-4 shadow-sm">
        <h3 className="mb-3 text-success">✅ Reservation Confirmed!</h3>
        <p><strong>Room:</strong> {roomTitle}</p>
        <p><strong>Check-in:</strong> {checkIn}</p>
        <p><strong>Check-out:</strong> {checkOut}</p>
        <hr />
        <p><strong>Payment ID:</strong> {paymentDetails.id}</p>
        <p><strong>Amount Paid:</strong> €{paymentDetails.amount}</p>
        <p><strong>Status:</strong> {paymentDetails.status || 'Completed'}</p>
        <button
          className="btn btn-primary mt-3"
          onClick={() => navigate('/')}
        >
          Back to Home
        </button>
      </div>
    </div>
  );
};

export default Confirmation;