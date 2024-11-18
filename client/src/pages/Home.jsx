import React from "react";
import { NavLink } from "react-router-dom";
import { Slider } from "../components/Slider";

export const Home = () => {
  return (
    <>
      <section className="relative font-body h-[80vh] bg-hero-img bg-cover bg-center bg-no-repeat bg-fixed">
        {/* Dark overlay */}
        <div className="absolute inset-0 bg-black opacity-50"></div>

        {/* Content */}
        <div className="relative z-10 text-white flex justify-center items-center h-full">
          <div className="flex flex-col items-center top-20 relative">
            {/* Call-to-Action */}
            <div className="relative group">
              <p
                className="
                text-lg font-bold relative z-10 px-6 rounded-full border-2 
                bg-[#333333ad] transition-all duration-300 ease-in-out transform-gpu
                group-hover:bg-[#333] group-hover:-translate-y-1 group-hover:scale-110
                group-hover:bg-opacity-100
              "
              >
                Looking for more customers!
                <NavLink to="/register" className="text-blue-600 ml-2">
                  Become a Vendor/Sign Up
                </NavLink>
              </p>
            </div>

            {/* Headings */}
            <h1 className="font-heading text-9xl font-bold mt-8 mb-6">
              Hungry!
            </h1>
            <h5 className="font-heading text-3xl font-bold mt-4 mb-8">
              Eat delicious home-cooked meals every day.
            </h5>

            {/* Search Content Box */}
            <div
              className="
              bg-gradient rounded-md mt-8 p-4 flex flex-col items-center justify-center
              transition-all duration-300 ease-in-out transform-gpu
              hover:-translate-y-1 hover:scale-105 shadow-lg
            "
            >
              <h5 className="text-xl font-heading text-[#333] font-bold py-4 px-6">
                TiffinCraft can help you find the right tiffin providers for
                your needs.
              </h5>

              {/* Search Input and Button */}
              <div className="flex flex-row justify-center items-center p-4 w-2/3">
                <input
                  type="text"
                  placeholder="Search for food..."
                  className="rounded-s-md py-1 px-2 w-full text-[#555] font-body font-medium focus:outline-none"
                />
                <button
                  className="
                  bg-gray-500 text-white font-bold font-body rounded-e-md py-1 px-6 w-[160px]
                  hover:bg-gray-600 transition duration-300
                "
                >
                  Find Food
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>
      <Slider />
    </>
  );
};
