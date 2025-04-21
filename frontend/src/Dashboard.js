import React, { useEffect, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { Container, Row, Col, Nav, Tab, Button, Card, Spinner } from "react-bootstrap";
import Pagesat from "./Pagesat"; // Import the Pagesat component

const Dashboard = () => {
  const navigate = useNavigate();  // Hook to navigate
  const location = useLocation();
  const queryParams = new URLSearchParams(location.search);
  const initialTab = queryParams.get("tab") || "reservations";

  const [reservations, setReservations] = useState([]);
  const [payments, setPayments] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      setLoading(true);
      try {
        const fetchedReservations = [{}, {}]; // Placeholder for fetched data
        const fetchedPayments = [
          { id: 1, amount: 5000, date: "2025-04-18" },
          { id: 2, amount: 3000, date: "2025-04-19" },
        ];

        setReservations(fetchedReservations);
        setPayments(fetchedPayments);
      } catch (error) {
        console.error("Error fetching data:", error);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  const handleLogout = () => {
    console.log("User logged out");
  };

  const handleGoToPayments = () => {
    navigate("/payments");
  };

  const handleUpdatePayments = (newPayment) => {
    setPayments((prevPayments) => [...prevPayments, newPayment]);
  };

  return (
    <Container className="mt-5">
      <Row>
        <Col md={3}>
          <h4>Dashboard</h4>
          <Button variant="btn btn-dark w-100" className="mb-3">
            Log Out
          </Button>
          <Nav variant="pills" className="flex-column">
            <Nav.Item>
              <Nav.Link eventKey="reservations">Rezervimet</Nav.Link>
            </Nav.Item>
            <Nav.Item>
              <Nav.Link eventKey="payments" onClick={handleGoToPayments}>
                Pagesat
              </Nav.Link>
            </Nav.Item>
          </Nav>
        </Col>
        <Col md={9}>
          <Tab.Container defaultActiveKey={initialTab}>
            <Tab.Content>
              <Tab.Pane eventKey="reservations">
                <h5>Rezervimet</h5>
                {loading ? (
                  <Spinner animation="border" />
                ) : reservations.length > 0 ? (
                  reservations.map((rez, index) => (
                    <Card className="mb-3" key={index}>
                      <Card.Body>
                        <Card.Title>Rezervim</Card.Title>
                        <Card.Text>Detaje rezervimi</Card.Text>
                      </Card.Body>
                    </Card>
                  ))
                ) : (
                  <p>Nuk ka rezervime.</p>
                )}
              </Tab.Pane>
              <Tab.Pane eventKey="payments">
                <h5>Pagesat</h5>
                <Pagesat payments={payments} onUpdatePayments={handleUpdatePayments} />
              </Tab.Pane>
            </Tab.Content>
          </Tab.Container>
        </Col>
      </Row>
    </Container>
  );
};

export default Dashboard;
