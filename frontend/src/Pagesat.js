import React, { useState } from 'react';
import 'bootstrap/dist/css/bootstrap.min.css';

const Pagesat = ({ payments, onUpdatePayments }) => {
  const [cards, setCards] = useState([
    { type: 'mastercard', last4: '3325' },
    { type: 'visa', last4: '6050' },
  ]);
  const [selectedCard, setSelectedCard] = useState('3325');

  const [cardNumber, setCardNumber] = useState('');
  const [cardType, setCardType] = useState('visa');
  const [cardholder, setCardholder] = useState('');
  const [bankName, setBankName] = useState('');
  const [cvv, setCvv] = useState('');
  const [error, setError] = useState('');

  const handleSelect = (last4) => {
    setSelectedCard(last4);
  };

  const handleAddCard = () => {
    if (!/^\d{16}$/.test(cardNumber)) {
      setError('Card number must be 16 digits.');
      return;
    }

    if (!/^\d{3}$/.test(cvv)) {
      setError('CVV must be 3 digits.');
      return;
    }

    if (!cardholder.trim()) {
      setError('Full name is required.');
      return;
    }

    if (!bankName.trim()) {
      setError('Bank name is required.');
      return;
    }

    const last4 = cardNumber.slice(-4);
    setCards([...cards, { type: cardType, last4 }]);

    // Reset fields
    setCardNumber('');
    setCardType('visa');
    setCardholder('');
    setBankName('');
    setCvv('');
    setError('');
    setSelectedCard(last4);
  };

  const handleCheckout = () => {
    const newPayment = {
      id: payments.length + 1,
      amount: 1000,
      date: new Date().toLocaleDateString(),
    };
    onUpdatePayments(newPayment);
  };

  const getCardLogo = (type) => {
    return type === 'visa'
      ? 'https://img.icons8.com/color/24/000000/visa.png'
      : 'https://img.icons8.com/color/24/000000/mastercard-logo.png';
  };

  return (
    <div className="container mt-5" style={{ maxWidth: '400px' }}>
      <div className="card shadow-sm p-3">
        <h5 className="mb-3">💳 Credit/Debit Card</h5>

    

        {/* Form always visible */}
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
          onChange={(e) => setCvv(e.target.value)}
          maxLength={3}
        />

        {error && <div className="text-danger mb-2">{error}</div>}


        <button className="mt-6 px-6 py-2 bg-black text-white rounded hover:bg-gray-800">
             Pay Now
            </button>
      </div>
    </div>
  );
};

export default Pagesat;
