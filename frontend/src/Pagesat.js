import React, { useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import "bootstrap/dist/css/bootstrap.min.css";
import axios from "axios";

const Pagesat = () => { 
  const location = useLocation();
  const navigate = useNavigate();

  const { roomId, roomTitle, checkIn, checkOut } = location.state || {};

  const [formData, setFormData] = useState({
    customerName: "",
    cardholder: "",
    bankName: "",
    cardNumber: "",
    cardType: "",
    cvv: "",
  });

  const [error, setError] = useState("");

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const validateCardNumber = (number) => {
    return /^\d{13,19}$/.test(number);
  };

  const validateCVV = (cvv) => {
    return /^\d{3,4}$/.test(cvv);
  };

  const formatDate = (date) => {
    try {
      const d = new Date(date);
      if (isNaN(d.getTime())) {
        throw new Error("Invalid date");
      }
      return d.toISOString().split("T")[0];
    } catch {
      setError("Date format is invalid.");
      return null;
    }
  };

  const handleCheckout = async () => {
    setError("");

    const { customerName, cardholder, bankName, cardNumber, cardType, cvv } = formData;

    if (!customerName || !cardholder || !bankName || !cardNumber || !cardType || !cvv) {
      setError("Please fill in all fields.");
      return;
    }

    if (!validateCardNumber(cardNumber)) {
      setError("Card number is not valid. It must contain 13-19 digits.");
      return;
    }

    if (!validateCVV(cvv)) {
      setError("CVV is not valid. It must contain 3 or 4 digits.");
      return;
    }

    const token = localStorage.getItem("token");
    if (!token) {
      setError("Please log in to make a reservation.");
      navigate("/login");
      return;
    }

    const checkInFormatted = formatDate(checkIn);
    const checkOutFormatted = formatDate(checkOut);

    if (!checkInFormatted || !checkOutFormatted) {
      setError("Check-in or check-out dates are invalid.");
      return;
    }

    const today = new Date().toISOString().split("T")[0];
    if (checkInFormatted < today) {
      setError("Check-in date cannot be in the past.");
      return;
    }
    if (checkOutFormatted <= checkInFormatted) {
      setError("Check-out date must be after check-in.");
      return;
    }

    const reservationData = {
      customer_name: customerName.trim(),
      check_in: checkInFormatted,
      check_out: checkOutFormatted,
      room_id: parseInt(roomId),
      user_id: parseInt(localStorage.getItem("user_id")) || null,
      status: "confirmed",
      payment: {
        cardholder: cardholder.trim(),
        bank_name: bankName.trim(),
        card_number: cardNumber.trim(),
        card_type: cardType.trim().toLowerCase(),
        cvv: cvv.trim(),
      },
    };
    try {
      const response = await axios.post("http://localhost:8000/api/checkout", reservationData, {
        headers: {
          Authorization: `Bearer ${token}`,
          "Content-Type": "application/json",
        },
      });

      const { reservation, payment } = response.data;

      navigate("/confirmation", {
        state: {
          reservationDetails: reservation,
          paymentDetails: payment,
          roomTitle,
          checkIn: checkInFormatted,
          checkOut: checkOutFormatted,
        },
      });
    } catch (err) {
      const errorMessage = err.response?.data?.error || err.response?.data?.messages || err.message || "Unknown error";
      setError(
        errorMessage.includes("Dhoma është e rezervuar") // "Room is reserved"
          ? "Sorry, the room is occupied for the dates you selected. Please choose another date or another room."
          : `Error processing reservation: ${JSON.stringify(errorMessage)}`
      );
    }
  };

  if (!roomId || !roomTitle || !checkIn || !checkOut) {
    return (
      <div className="container mt-5 text-center text-danger">
        Room information is missing. Please go back and select a room.
      </div>
    );
  }

  return (
    <div className="container mt-5" style={{ maxWidth: "600px" }}>
      <div className="card shadow-sm p-4">
        <h5 className="mb-3">📋 Reservation for {roomTitle}</h5>
        <p className="text-muted">
          Check-In: {checkIn} | Check-Out: {checkOut}
        </p>

        <div className="mb-3">
          <label htmlFor="customerName" className="form-label">
            Your Name
          </label>
          <input
            type="text"
            className="form-control"
            id="customerName"
            name="customerName"
            value={formData.customerName}
            onChange={handleChange}
            placeholder="Enter your name"
          />
        </div>

        <div className="mb-3">
          <label htmlFor="cardholder" className="form-label">
            Cardholder Name
          </label>
          <input
            type="text"
            className="form-control"
            id="cardholder"
            name="cardholder"
            value={formData.cardholder}
            onChange={handleChange}
            placeholder="Enter cardholder's name"
          />
        </div>

        <div className="mb-3">
          <label htmlFor="bankName" className="form-label">
            Bank Name
          </label>
          <input
            type="text"
            className="form-control"
            id="bankName"
            name="bankName"
            value={formData.bankName}
            onChange={handleChange}
            placeholder="Enter bank name"
          />
        </div>

        <div className="mb-3">
          <label htmlFor="cardNumber" className="form-label">
            Card Number
          </label>
          <input
            type="text"
            className="form-control"
            id="cardNumber"
            name="cardNumber"
            value={formData.cardNumber}
            onChange={handleChange}
            placeholder="Enter card number "
            maxLength="19"
          />
        </div>

        <div className="mb-3">
          <label htmlFor="cardType" className="form-label">
            Card Type
          </label>
          <select
            className="form-control"
            id="cardType"
            name="cardType"
            value={formData.cardType}
            onChange={handleChange}
          >
            <option value="">Select card type</option>
            <option value="visa">Visa</option>
            <option value="mastercard">MasterCard</option>
          </select>
        </div>

        <div className="mb-3">
          <label htmlFor="cvv" className="form-label">
            CVV
          </label>
          <input
            type="text"
            className="form-control"
            id="cvv"
            name="cvv"
            value={formData.cvv}
            onChange={handleChange}
            placeholder="Enter CVV "
            maxLength="4" 
          />
        </div>

        {error && <div className="text-danger mb-3">{error}</div>}

        <button className="btn btn-dark w-100" onClick={handleCheckout}>
          Confirm Reservation
        </button>
      </div>
    </div>
  );
};

export default Pagesat; 