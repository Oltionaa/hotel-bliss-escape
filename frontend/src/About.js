import React from 'react';
import 'bootstrap/dist/css/bootstrap.min.css';

function About() {
  return (
    <div id="about" className="bg-white text-black font-sans">
      {/* Hero Section */}
      <section className="max-w-screen-xl mx-auto px-6 py-20 text-center">
        <h2 className="text-4xl md:text-5xl font-bold mb-4 leading-tight">
          Setting New Standards in <span className="italic text-blue-700">Luxury Hotel Experiences</span>
        </h2>
        <p className="text-gray-600 max-w-2xl mx-auto">
          At Hotel Bliss Escape, we craft more than stays—we create memories.
        </p>
        <img
          src="https://i.pinimg.com/474x/55/5c/3f/555c3f6b5050a0ddc53a603475bef89c.jpg"
          alt="Modern Hotel"
          className="rounded-3xl w-full mt-10 shadow-lg"
        />
      </section>

      {/* Vision Section */}
      <section className="max-w-screen-xl mx-auto px-6 py-16 grid md:grid-cols-3 gap-8 items-center">
        <img
          
          className="rounded-2xl shadow-md"
        />
        <div className="md:col-span-2 text-center md:text-left">
          <h3 className="text-3xl font-bold mb-4">
            If you can <span className="italic text-blue-700">dream it</span>, we can <span className="italic text-blue-700">build it</span>.
          </h3>
          <p className="text-gray-700 leading-relaxed">
            Our hotel management philosophy revolves around excellence. From personalized guest services to tech-enabled room booking, we innovate to offer a truly unique experience.
          </p>
          <a href="#contact">
            <button className="mt-6 px-6 py-2 bg-black text-white rounded hover:bg-gray-800">
              Get in touch
            </button>
          </a>
        </div>
      </section>

      {/* Feature Section */}
      <section className="bg-gray-100 py-16">
        <div className="max-w-screen-xl mx-auto px-6 text-center">
          <h4 className="text-3xl font-semibold mb-4">Our Timeless <span className="italic text-blue-700">Hospitality</span></h4>
          <p className="text-gray-700 mb-8 max-w-2xl mx-auto">
            We've been redefining hotel experiences with tech-savvy check-ins, curated local travel guides, and dedicated concierge support—all tailored for the modern traveler.
          </p>
          <img
            src="https://i.pinimg.com/474x/a9/cf/2e/a9cf2e0737c7c73a9b160e37107fe9a6.jpg"
            alt="Luxury Dining Area"
            className="rounded-3xl shadow-xl w-full"
          />
        </div>
      </section>
    </div>
  );
}

export default About;