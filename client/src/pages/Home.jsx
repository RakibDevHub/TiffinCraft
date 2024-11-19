import React from "react";
import { NavLink } from "react-router-dom";
// import { Slider } from "../components/Slider";

import { GrGroup } from "react-icons/gr";
import { TbListSearch } from "react-icons/tb";
import { MdOutlineDoubleArrow } from "react-icons/md";
import { IoInfiniteSharp, IoBonfire } from "react-icons/io5";

import { FaOpencart } from "react-icons/fa";

import image_1 from "../images/step_1.png";
import image_2 from "../images/step_2.png";
import image_3 from "../images/step_3.png";

export const Home = () => {
  return (
    <>
      <section className="relative font-body h-[100vh] bg-hero-img bg-cover bg-center bg-no-repeat bg-fixed">
        {/* Dark overlay */}
        <div className="absolute inset-0 bg-[#333] opacity-50"></div>

        {/* Content */}
        <div className="relative z-10 text-white flex justify-center items-center h-full">
          <div className="flex flex-col items-center relative">
            <div className="relative group">
              <p
                className="
                text-lg font-bold relative z-10 px-6 rounded-full border-2 
                bg-[#333333ad] transition-all duration-500 ease-in-out transform-gpu
                group-hover:bg-[#333] group-hover:-translate-y-1 group-hover:scale-110
                group-hover:bg-opacity-100
              "
              >
                Looking for more customers!
                <NavLink to="/register" className="text-green-400 ml-2">
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
            <div className="flex gap-4">
              <NavLink
                to="#"
                className="text-[#555] flex flex-row items-center gap-2 uppercase bg-orange-400 text-whites font-bold font-heading rounded-md py-2 px-6 border border-orange-400 hover:bg-orange-500 hover:border-orange-500 transition-colors duration-500"
              >
                Order now
                <FaOpencart className="text-lg" />
              </NavLink>
              <NavLink
                to={"#how"}
                className="flex flex-row items-center gap-2 uppercase bg-[#555555a6] text-orange-400 font-bold font-heading rounded-md py-2 px-6 border border-orange-400 hover:bg-[#555] transition-colors duration-500"
              >
                How it works
                <MdOutlineDoubleArrow className="text-lg rotate-90" />
              </NavLink>
            </div>
          </div>
        </div>
      </section>

      <section className=" flex flex-col gap-8 justify-center items-center p-16 shadow-sm">
        <h1 className="text-4xl font-bold font-heading uppercase">
          GET CONNECTED FAST NOT ANY AVERAGE PLATFORM
        </h1>
        <p className="text-center font-bold font-body w-4/5 text-lg">
          Welcome to <span className="text-orange-400">TiffinCraft</span>, your
          ultimate destination for homemade food enthusiasts and culinary
          experts alike. Whether you're a passionate home cook looking to
          showcase your skills or someone with a discerning palate in search of
          authentic home cooked meals,{" "}
          <span className="text-orange-400">TiffinCraft</span> is here to unite
          food lovers from all walks of life.
        </p>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-8 w-3/4">
          <div className="flex flex-col border p-4 rounded-md shadow-md">
            <span className="flex items-center text-7xl">
              <IoInfiniteSharp className="text-orange-400" />
            </span>
            <h2 className="font-bold font-heading text-lg py-2">
              Explore Endless Possibilities
            </h2>
            <p className="font-body">
              Unleash your creativity and explore a diverse range of homemade
              recipes with TiffinCraft. From traditional favorites to innovative
              creations, there's something for everyone to enjoy.
            </p>
          </div>
          <div className="flex flex-col border p-4 rounded-md shadow-md">
            <span className="flex items-center text-7xl">
              <TbListSearch className="text-orange-400" />
            </span>
            <h2 className="font-bold font-heading text-lg py-2">
              Discover Homemade Delights
            </h2>
            <p className="font-body">
              Indulge in a world of homemade goodness with TiffinCraft. Explore,
              share, and savor delicious homemade dishes from passionate cooks
              like you.
            </p>
          </div>
          <div className="flex flex-col border p-4 rounded-md shadow-md">
            <span className="flex items-center text-7xl">
              <IoBonfire className="text-orange-400" />
            </span>
            <h2 className="font-bold font-heading text-lg py-2">
              Share Your Passion
            </h2>
            <p className="font-body">
              Share your love for cooking and connect with fellow food
              enthusiasts on TiffinCraft. Showcase your culinary talents, swap
              recipes, and inspire others with your homemade delights.
            </p>
          </div>
          <div className="flex flex-col border p-4 rounded-md shadow-md">
            <span className="flex items-center text-7xl">
              <GrGroup className="text-orange-400" />
            </span>
            <h2 className="font-bold font-heading text-lg py-2">
              Join Our Community
            </h2>
            <p className="font-body">
              Join our welcoming community of food lovers and embark on a
              flavorful journey with TiffinCraft. Whether you're a seasoned chef
              or a cooking novice, there's always room at our table for you.
            </p>
          </div>
        </div>
      </section>

      {/* <section className="p-16 relative bg-[#FFFAE6]" id="how"> */}
      <section
        className="p-16 flex flex-col justify-center items-center"
        id="how"
      >
        <h1 className="text-4xl uppercase font-heading font-bold mb-24">
          How it Works: Easy Steps to Get Started
        </h1>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-12 w-3/4">
          {/* Step 1 */}
          <div className="flex flex-col justify-center">
            <span className="font-heading font-extrabold text-9xl text-orange-200 hover:text-orange-300 transition-all duration-500">
              01
            </span>
            <h1 className="font-heading font-bold text-4xl my-4 ml-2 text-orange-300">
              Find Your Vendor
            </h1>
            <p className="font-body text-lg text-gray-700 ml-2">
              Browse through our trusted vendors and pick the one that matches
              your requirements. It's easy to sign up and create your account.
            </p>
          </div>
          <div className="flex justify-center relative">
            <div className="absolute bg-orange-300 w-80 h-80 rounded-full -top-8 -left-8 opacity-80"></div>
            <div className="absolute bg-orange-200 w-60 h-60 rounded-full -bottom-4 -right-6 opacity-70"></div>
            <img
              src={image_1}
              alt="Step 1: A person holding phone"
              className="relative z-10 h-[500px] w-auto"
            />
          </div>
          {/* Step 2 */}
          <div className="flex justify-center relative">
            <div
              className="absolute bg-blue-200 w-60 h-60 transform rotate-45 -top-10 -left-10 opacity-80"
              style={{ borderRadius: "15%" }}
            ></div>
            <div
              className="absolute bg-blue-400 w-40 h-40 transform rotate-45 -bottom-8 -right-0 opacity-70"
              style={{ borderRadius: "15%" }}
            ></div>
            <img
              src={image_2}
              alt="Step 2: A person talking on the phone with the vendors"
              className="relative z-10 h-[500px] w-auto"
            />
          </div>
          <div className="flex flex-col justify-center">
            <span className="font-heading font-extrabold text-9xl text-blue-200 hover:text-blue-400 transition-all duration-500">
              02
            </span>
            <h1 className="font-heading font-bold text-4xl my-4 ml-2 text-blue-400">
              Customize Your Plan
            </h1>
            <p className="font-body text-lg text-gray-700 ml-2">
              Communicate with the vendor to design your ideal meal plan. You
              can tailor it to your preferences and needs.
            </p>
          </div>
          {/* Step 3 */}
          <div className="flex flex-col justify-center">
            <span className="font-heading font-extrabold text-9xl text-green-200 hover:text-green-400 transition-all duration-500">
              03
            </span>
            <h1 className="font-heading font-bold text-4xl my-4 ml-2 text-green-400">
              Enjoy Hassle-Free Meals
            </h1>
            <p className="font-body text-lg text-gray-700 ml-2">
              Sit back and enjoy your meal deliveries—freshly prepared and
              delivered right to your doorstep.
            </p>
          </div>
          <div className="flex justify-center relative">
            {/* Design 3: Layered Waves */}
            <div
              className="absolute bg-green-200 w-64 h-20 rounded-full top-20 -left-0 opacity-80"
              style={{ transform: "skewX(-30deg)" }}
            ></div>
            <div
              className="absolute bg-green-400 w-44 h-16 rounded-full -bottom-8 -right-6 opacity-70"
              style={{ transform: "skewX(-30deg)" }}
            ></div>
            <img
              src={image_3}
              alt="Step 3: A person delivering food to the customer"
              className="relative z-10 h-[500px] w-auto"
            />
          </div>
        </div>
      </section>

      {/* <Slider /> */}
    </>
  );
};
