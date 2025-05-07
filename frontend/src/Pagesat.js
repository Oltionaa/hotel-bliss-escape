import React, { useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import "bootstrap/dist/css/bootstrap.min.css";
import axios from "axios";

const Pagesat = () => {
  const location = useLocation();
  const navigate = useNavigate();

  const { roomId, roomTitle, checkIn, checkOut } = location.state || {};

  console.log("Pagesat state:", { roomId, roomTitle, checkIn, checkOut });

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
    setFormData((prevData) => ({
      ...prevData,
      [name]: value,
    }));
  };

  const handleCheckout = async () => {
    const { customerName, cardholder, bankName, cardNumber, cardType, cvv } = formData;

    if (!customerName || !cardholder || !bankName || !cardNumber || !cardType || !cvv) {
      setError("Ju lutem plotësoni të gjitha fushat.");
      return;
    }

    const token = localStorage.getItem("token");
    if (!token) {
      setError("Ju lutem identifikohuni për të bërë rezervimin.");
      navigate("/login");
      return;
    }

    const reservationData = {
      customer_name: customerName,
      check_in: checkIn,
      check_out: checkOut,
      room_id: roomId,
      user_id: localStorage.getItem("user_id"), // Shto user_id
      status: "confirmed",
      payment: {
        cardholder,
        bank_name: bankName,
        card_number: cardNumber,
        card_type: cardType,
        cvv,
      },
    };

    console.log("Data being sent to /api/checkout:", reservationData);

    try {
      const response = await axios.post(
        "http://localhost:8000/api/checkout",
        reservationData,
        {
          headers: {
            Authorization: `Bearer ${token}`, // Shto autorizim
            "Content-Type": "application/json",
          },
        }
      );
      console.log("Checkout response:", response.data);
      const newReservation = response.data.reservation;
      const newPayment = response.data.payment;

      navigate("/confirmation", {
        state: {
          reservationDetails: newReservation,
          paymentDetails: newPayment,
          roomTitle,
          checkIn,
          checkOut,
        },
      });
    } catch (err) {
      const errorMessage =
        err.response?.data?.message || err.message || "Gabim i panjohur";
      setError("Gabim gjatë procesimit të rezervimit: " + errorMessage);
      console.error("Checkout error:", err.response?.data || err);
    }
  };

  if (!roomId || !roomTitle || !checkIn || !checkOut) {
    return (
      <div className="container mt-5 text-center text-danger">
        Informacioni i dhomës mungon. Ju lutem kthehuni dhe zgjidhni një dhomë.
      </div>
    );
  }

  return (
    <div className="container mt-5" style={{ maxWidth: "600px" }}>
      <div className="card shadow-sm p-4">
        <h5 className="mb-3">📋 Rezervimi për {roomTitle}</h5>
        <p className="text-muted">
          Check-In: {checkIn} | Check-Out: {checkOut}
        </p>
        <div className="mb-3">
          <label htmlFor="customerName" className="form-label">
            Emri Juaj
          </label>
          <input
            type="text"
            className="form-control"
            id="customerName"
            name="customerName"
            value={formData.customerName}
            onChange={handleChange}
            placeholder="Shkruani emrin tuaj"
          />
        </div>
        <div className="mb-3">
          <label htmlFor="cardholder" className="form-label">
            Emri i Mbajtësit të Kartës
          </label>
          <input
            type="text"
            className="form-control"
            id="cardholder"
            name="cardholder"
            value={formData.cardholder}
            onChange={handleChange}
            placeholder="Shkruani emrin e mbajtësit të kartës"
          />
        </div>
        <div className="mb-3">
          <label htmlFor="bankName" className="form-label">
            Emri i Bankës
          </label>
          <input
            type="text"
            className="form-control"
            id="bankName"
            name="bankName"
            value={formData.bankName}
            onChange={handleChange}
            placeholder="Shkruani emrin e bankës"
          />
        </div>
        <div className="mb-3">
          <label htmlFor="cardNumber" className="form-label">
            Numri i Kartës
          </label>
          <input
            type="text"
            className="form-control"
            id="cardNumber"
            name="cardNumber"
            value={formData.cardNumber}
            onChange={handleChange}
            placeholder="Shkruani numrin e kartës"
          />
        </div>
        <div className="mb-3">
          <label htmlFor="cardType" className="form-label">
            Tipi i Kartës
          </label>
          <input
            type="text"
            className="form-control"
            id="cardType"
            name="cardType"
            value={formData.cardType}
            onChange={handleChange}
            placeholder="p.sh., Visa, MasterCard"
          />
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
            placeholder="Shkruani CVV"
          />
        </div>
        {error && <div className="text-danger mb-3">{error}</div>}
        <button className="btn btn-dark w-100" onClick={handleCheckout}>
          Konfirmo Rezervimin
        </button>
      </div>
    </div>
  );
};

export default Pagesat;