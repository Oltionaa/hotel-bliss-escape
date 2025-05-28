import React, { useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import "bootstrap/dist/css/bootstrap.min.css";
import axios from "axios";

const Pagesat = () => {
  const location = useLocation();
  const navigate = useNavigate();

  // Merr të dhënat nga location.state
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

  // Funksion për të trajtuar ndryshimet në input
  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  // Validim për numrin e kartës (13-19 shifra, vetëm numra)
  const validateCardNumber = (number) => {
    return /^\d{13,19}$/.test(number);
  };

  // Validim për CVV (3-4 shifra)
  const validateCVV = (cvv) => {
    return /^\d{3,4}$/.test(cvv);
  };

  // Funksion për të formatuar datat në YYYY-MM-DD
  const formatDate = (date) => {
    try {
      const d = new Date(date);
      if (isNaN(d.getTime())) {
        throw new Error("Data e pavlefshme");
      }
      return d.toISOString().split("T")[0]; // Kthen YYYY-MM-DD
    } catch {
      setError("Formati i datës është i pavlefshëm.");
      return null;
    }
  };

  // Funksion për të kryer checkout-in
  const handleCheckout = async () => {
    setError("");

    const { customerName, cardholder, bankName, cardNumber, cardType, cvv } = formData;

    // Validimi në frontend
    if (!customerName || !cardholder || !bankName || !cardNumber || !cardType || !cvv) {
      setError("Ju lutem plotësoni të gjitha fushat.");
      return;
    }

    if (!validateCardNumber(cardNumber)) {
      setError("Numri i kartës nuk është valid. Duhet të përmbajë 13-19 shifra.");
      return;
    }

    if (!validateCVV(cvv)) {
      setError("CVV nuk është valid. Duhet të përmbajë 3 ose 4 shifra.");
      return;
    }

    const token = localStorage.getItem("token");
    if (!token) {
      setError("Ju lutem identifikohuni për të bërë rezervimin.");
      navigate("/login");
      return;
    }

    // Formatimi i datave
    const checkInFormatted = formatDate(checkIn);
    const checkOutFormatted = formatDate(checkOut);

    if (!checkInFormatted || !checkOutFormatted) {
      setError("Datat e check-in ose check-out janë të pavlefshme.");
      return;
    }

    // Validim shtesë për datat
    const today = new Date().toISOString().split("T")[0];
    if (checkInFormatted < today) {
      setError("Data e check-in nuk mund të jetë në të kaluarën.");
      return;
    }
    if (checkOutFormatted <= checkInFormatted) {
      setError("Data e check-out duhet të jetë pas check-in.");
      return;
    }

    // Përgatitja e të dhënave për dërgim
    const reservationData = {
      customer_name: customerName.trim(),
      check_in: checkInFormatted,
      check_out: checkOutFormatted,
      room_id: parseInt(roomId), // Sigurohu që është numër
      user_id: parseInt(localStorage.getItem("user_id")) || null, // Null nëse mungon
      status: "confirmed",
      payment: {
        cardholder: cardholder.trim(),
        bank_name: bankName.trim(),
        card_number: cardNumber.trim(),
        card_type: cardType.trim().toLowerCase(), // Normalizo në lowercase
        cvv: cvv.trim(),
      },
    };

    // Log për debugging
    console.log("Të dhënat e dërguara:", JSON.stringify(reservationData, null, 2));

    try {
      const response = await axios.post("http://localhost:8000/api/checkout", reservationData, {
        headers: {
          Authorization: `Bearer ${token}`,
          "Content-Type": "application/json",
        },
      });

      const { reservation, payment } = response.data;

      // Navigo te faqja e konfirmimit
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
      console.error("Përgjigjja e gabimit nga serveri:", err.response?.data);
      const errorMessage = err.response?.data?.error || err.response?.data?.messages || err.message || "Gabim i panjohur";
      setError(
        errorMessage.includes("Dhoma është e rezervuar")
          ? "Më vjen keq, dhoma është e zënë për datat që zgjodhët. Ju lutem zgjidhni një datë tjetër ose një dhomë tjetër."
          : `Gabim gjatë procesimit të rezervimit: ${JSON.stringify(errorMessage)}`
      );
    }
  };

  // Kontrollo nëse të dhënat fillestare mungojnë
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
            type="text" // Ndërruar në text për të kontrolluar input-in më mirë
            className="form-control"
            id="cardNumber"
            name="cardNumber"
            value={formData.cardNumber}
            onChange={handleChange}
            placeholder="Shkruani numrin e kartës (16 shifra)"
            maxLength="19"
          />
        </div>

        <div className="mb-3">
          <label htmlFor="cardType" className="form-label">
            Tipi i Kartës
          </label>
          <select
            className="form-control"
            id="cardType"
            name="cardType"
            value={formData.cardType}
            onChange={handleChange}
          >
            <option value="">Zgjidh tipin e kartës</option>
            <option value="visa">Visa</option>
            <option value="mastercard">MasterCard</option>
          </select>
        </div>

        <div className="mb-3">
          <label htmlFor="cvv" className="form-label">
            CVV
          </label>
          <input
            type="text" // Ndërruar në text për kontroll më të mirë
            className="form-control"
            id="cvv"
            name="cvv"
            value={formData.cvv}
            onChange={handleChange}
            placeholder="Shkruani CVV (3 shifra)"
            maxLength="3"
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