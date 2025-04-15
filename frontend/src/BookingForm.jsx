function BookingForm() {
    return (
      <div className="container bg-light py-4 mt-n5 position-relative" style={{ marginTop: '-50px' }}>
        <div className="row g-2">
          <div className="col-md">
            <label className="form-label">Check in</label>
            <input type="date" className="form-control" />
          </div>
          <div className="col-md">
            <label className="form-label">Check out</label>
            <input type="date" className="form-control" />
          </div>
          <div className="col-md">
            <label className="form-label">Adults</label>
            <select className="form-select">
              <option>1 Adult</option>
              <option>2 Adults</option>
              <option>3 Adults</option>
            </select>
          </div>
          <div className="col-md">
            <label className="form-label">Kids</label>
            <select className="form-select">
              <option>No Kids</option>
              <option>1 Kid</option>
              <option>2 Kids</option>
            </select>
          </div>
          <div className="col-md d-flex align-items-end">
          <button className="btn btn-dark">Check Now</button>



          </div>
        </div>
      </div>
    );
  }
  
  export default BookingForm;