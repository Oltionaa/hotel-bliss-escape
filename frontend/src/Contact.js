import React from 'react';

const Contact = () => {
  return (
    <div id="contact" className="container py-5">
      <h2 className="fw-bold">CONTACT US</h2>
      <div className="row text-center mb-5">
        <div className="col-md-3">
          <i className="bi bi-geo-alt-fill fs-3"></i>
          <h6 className="italic text-blue-700">ADDRESS</h6>
          <p className="mb-0 fw-bold">1234 Divi St. #1000</p>
          <p>PRISHTINE</p>
        </div>
        <div className="col-md-3">
          <i className="bi bi-telephone-fill fs-3"></i>
          <h6 className="italic text-blue-700">PHONE</h6>
          <p className="italic text-blue-700">044 352-6258</p>
        </div>
        <div className="col-md-3">
          <i className="bi bi-clock-fill fs-3"></i>
          <h6 className="italic text-blue-700">HOURS</h6>
          <p className="fw-bold">Monday - Friday</p>
          <p>8am - 11pm</p>
        </div>
        <div className="col-md-3">
          <i className="bi bi-envelope-fill fs-3"></i>
          <h6 className="italic text-blue-700">SUPPORT</h6>
          <p><a href="mailto:support@divishorerepair.com" className="italic text-blue-700">blissEscape@gmail.com</a></p>
        </div>
      </div>

    
    </div>
  );
};

export default Contact;