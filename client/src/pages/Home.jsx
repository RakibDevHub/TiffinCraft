import React from "react";
import { NavLink } from "react-router-dom";
import { Slider } from "../components/Slider";

import { GrGroup } from "react-icons/gr";
import { TbListSearch } from "react-icons/tb";
import { MdOutlineDoubleArrow } from "react-icons/md";
import { IoInfiniteSharp, IoBonfire } from "react-icons/io5";

import { FaOpencart } from "react-icons/fa";

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
                bg-[#333333ad] transition-all duration-300 ease-in-out transform-gpu
                group-hover:bg-[#333] group-hover:-translate-y-1 group-hover:scale-110
                group-hover:bg-opacity-100
              "
              >
                Looking for more customers!
                <NavLink to="/register" className="text-orange-400 ml-2">
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
              <button className="flex flex-row items-center gap-2 uppercase bg-orange-400 text-whites font-bold font-heading rounded-md py-2 px-6 border border-orange-400 hover:bg-orange-500 ">
                Order now
                <FaOpencart className="text-lg" />
              </button>
              <button className="flex flex-row items-center gap-2 uppercase bg-[#555555a6] text-orange-400 font-bold font-heading rounded-md py-2 px-6 border border-orange-400 hover:bg-[#555]">
                Learn more
                <MdOutlineDoubleArrow className="text-lg rotate-90" />
              </button>
            </div>
            {/* Search Content Box */}
            {/* <div
              className="
              bg-gradient rounded-md p-4 flex flex-col items-center justify-center
              transition-all duration-300 ease-in-out transform-gpu
              hover:-translate-y-1 hover:scale-105 shadow-lg
            "
            >
              <h5 className="text-xl font-heading text-[#fff] font-bold py-4 px-6">
                TiffinCraft can help you find the right tiffin providers for
                your needs.
              </h5>

              <div className="flex flex-row justify-center items-center p-4 w-2/3">
                <input
                  type="text"
                  placeholder="Search for food..."
                  className="rounded-s-md py-1 px-2 w-full text-[#555] font-body font-medium focus:outline-none"
                />
                <button
                  className="
                  bg-[#555555c5] text-white font-bold font-body rounded-e-md py-1 px-6 w-[160px]
                  hover:bg-[#555] transition duration-300
                "
                >
                  Find Food
                </button>
              </div>
            </div> */}
          </div>
        </div>
      </section>

      <section className=" flex flex-col gap-8 justify-center items-center p-16 shadow-md">
        <h1 className="text-4xl font-bold font-heading uppercase">
          GET CONNECTED FAST NOT YOUR AVERAGE PLATFORM
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
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-8">
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
      <section className="p-16 relative bg-[#FFFAE6]">
        <div className="">
          <h1>Check out the foods</h1>
        </div>
        <Slider />
      </section>
    </>
  );
};
