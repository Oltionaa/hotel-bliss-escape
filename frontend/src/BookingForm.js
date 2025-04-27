import React from 'react';

export default function BookingForm({ onSearch, onChange, formData }) {
  return (
    <div className="container mt-5">
      <h3 className="text-center mb-4">Find Your Room</h3>
      <form>
        <div className="row">
          <div className="col-md-6">
            <label htmlFor="capacity" className="form-label">Capacity</label>
            <input
              type="number"
              className="form-control"
              id="capacity"
              name="capacity"
              value={formData.capacity}
              onChange={onChange}
            />
          </div>
          <div className="col-md-6">
            <label htmlFor="date" className="form-label">Date</label>
            <input
              type="date"
              className="form-control"
              id="date"
              name="date"
              value={formData.date}
              onChange={onChange}
            />
          </div>
        </div>
        <button type="button" className="btn btn-dark " onClick={onSearch}>
          Search Rooms
        </button>
      </form>
    </div>
  );
}
