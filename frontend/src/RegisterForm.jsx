import React, { useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import 'bootstrap/dist/css/bootstrap.min.css';

const Pagesat = () => {
  const location = useLocation();
  const navigate = useNavigate();

  const { roomTitle, roomPrice, checkIn, checkOut, people } = location.state || {};

  const [cardNumber, setCardNumber] = useState('');
  const [cardType, setCardType] = useState('visa');
  const [cardholder, setCardholder] = useState('');
  const [bankName, setBankName] = useState('');
  const [cvv, setCvv] = useState('');
  const [error, setError] = useState('');

  const handleCheckout = () => {
    // Validimi bazik
    if (!cardholder || !bankName || !cardNumber || !cvv) {
      setError('Please fill in all fields.');
      return;
    }

    if (cardNumber.length !== 16 || isNaN(cardNumber)) {
      setError('Card number must be 16 digits.');
      return;
    }

    if (cvv.length !== 3 || isNaN(cvv)) {
      setError('CVV must be 3 digits.');
      return;
    }

    if (!roomPrice || !roomTitle) {
      setError('Failed to process reservation.');
      return;
    }

    // Pagesa është në rregull
    const newPayment = {
      id: Date.now(),
      amount: roomPrice,
      date: new Date().toLocaleDateString(),
    };

    navigate('/confirmation', {
      state: {
        paymentDetails: newPayment,
        roomTitle,
        checkIn,
        checkOut,
        people,
      }
    });
  };

  const getCardLogo = (type) => {
    return type === 'visa'
      ? 'https://img.icons8.com/color/24/000000/visa.png'
      : 'https://img.icons8.com/color/24/000000/mastercard-logo.png';
  };

  // Nëse nuk ka të dhëna nga room
  if (!roomTitle || !roomPrice) {
    return (
      <div className="container mt-5 text-center text-danger">
        Room information missing. Please go back and select a room.
      </div>
    );
  }

  return (
    <div className="container mt-5" style={{ maxWidth: '600px' }}>
      <div className="card shadow-sm p-4">
        <h5 className="mb-3">💳 Credit/Debit Card Payment</h5>

        <input
          type="text"
          className="form-control mb-2"
          placeholder="Full Name"
          value={cardholder}
          onChange={(e) => setCardholder(e.target.value)}
        />

        <input
          type="text"
          className="form-control mb-2"
          placeholder="Bank Name"
          value={bankName}
          onChange={(e) => setBankName(e.target.value)}
        />

        <input
          type="text"
          className="form-control mb-2"
          placeholder="Enter 16-digit card number"
          value={cardNumber}
          maxLength={16}
          onChange={(e) => setCardNumber(e.target.value)}
        />

        <div className="d-flex align-items-center mb-2">
          <img
            src={getCardLogo(cardType)}
            alt={cardType}
            className="me-2"
            style={{ width: 30 }}
          />
          <select
            className="form-select"
            value={cardType}
            onChange={(e) => setCardType(e.target.value)}
          >
            <option value="visa">Visa</option>
            <option value="mastercard">MasterCard</option>
          </select>
        </div>

        <input
          type="text"
          className="form-control mb-2"
          placeholder="CVV (3 digits)"
          value={cvv}
          maxLength={3}
          onChange={(e) => setCvv(e.target.value)}
        />

        {error && <div className="text-danger mb-3">{error}</div>}

        <button
          className="btn btn-dark w-100"
          onClick={handleCheckout}
        >
          Pay Now ${roomPrice}
        </button>
      </div>
    </div>
  );
};

export default Pagesat;