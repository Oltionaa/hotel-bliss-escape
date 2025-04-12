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

      <form className="row g-3">
        <div className="col-md-6">
          <input type="text" className="form-control border-success" placeholder="First Name" />
        </div>
        <div className="col-md-6">
          <input type="text" className="form-control border-success" placeholder="Last Name" />
        </div>
        <div className="col-md-6">
          <input type="email" className="form-control border-success" placeholder="Email Address" />
        </div>
        <div className="col-md-6">
          <input type="tel" className="form-control border-success" placeholder="Phone" />
        </div>
        <div className="col-12">
          <input type="text" className="form-control border-success" placeholder="Subject" />
        </div>
        <div className="col-12">
          <textarea className="form-control border-success" placeholder="Message" rows="5"></textarea>
        </div>
        <div className="col-12 text-end">
          <button type="submit" className="mt-6 px-6 py-2 bg-black text-white rounded hover:bg-gray-800">SUBMIT</button>
        </div>
      </form>
    </div>
  );
};

export default Contact;