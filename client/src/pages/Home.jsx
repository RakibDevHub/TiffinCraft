import React from "react";
import { NavLink } from "react-router-dom";
import { GrGroup } from "react-icons/gr";
import { TbListSearch } from "react-icons/tb";
// import { MdOutlineQuestionMark, MdOutlineDoubleArrow } from "react-icons/md";
import { IoInfiniteSharp, IoBonfire } from "react-icons/io5";
import image_1 from "../images/step_1.png";
import image_2 from "../images/step_2.png";
import image_3 from "../images/step_3.png";
import { Slider } from "../components/Slider";

// Hero Links
const heroLinks = [
  { name: "What is TiffinCraft?", path: "#what" },
  { name: "How it works?", path: "#how" },
  { name: "Explore Vendors!", path: "#vendors" },
  { name: "Explore Food Items!", path: "#foods" },
  { name: "Become a seller register now!", path: "/register" },
];

const features = [
  {
    icon: <IoInfiniteSharp className="text-orange-400" />,
    title: "Explore Endless Possibilities",
    description:
      "Unleash your creativity and explore a diverse range of homemade recipes with TiffinCraft. From traditional favorites to innovative creations, there's something for everyone.",
  },
  {
    icon: <TbListSearch className="text-orange-400" />,
    title: "Discover Homemade Delights",
    description:
      "Indulge in a world of homemade goodness with TiffinCraft. Explore, share, and savor delicious homemade dishes from passionate cooks like you.",
  },
  {
    icon: <IoBonfire className="text-orange-400" />,
    title: "Share Your Passion",
    description:
      "Share your love for cooking and connect with fellow food enthusiasts. Showcase your culinary talents and inspire others with your homemade delights.",
  },
  {
    icon: <GrGroup className="text-orange-400" />,
    title: "Join Our Community",
    description:
      "Join our welcoming community of food lovers and embark on a flavorful journey. Whether you're a seasoned chef or a novice, there's always room at our table.",
  },
];

export const Home = () => {
  return (
    <>
      {/* Hero Section */}
      <section
        id="home"
        className="relative font-body h-[100vh] bg-hero-img bg-cover bg-center bg-no-repeat bg-fixed"
      >
        {/* Dark Overlay */}
        <div className="absolute inset-0 bg-[#333] opacity-50"></div>

        {/* Main Content */}
        <div className="relative z-10 flex justify-between items-center h-full p-20 text-orange-50">
          <div className="flex flex-col lg:flex-row gap-20 justify-center">
            {/* Headings */}
            <div>
              <h1 className="font-heading text-9xl font-bold mb-6">Hungry!</h1>
              <h5 className="font-heading text-3xl font-bold mt-4 ml-2">
                What are you waiting for!
                <br />
                Eat delicious home-cooked meals every day.
              </h5>
            </div>

            {/* Hero Links */}
            <div className="grid gap-4 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-1 justify-center items-center">
              {heroLinks.map((link, index) => (
                <NavLink
                  key={index}
                  to={link.path}
                  className="font-heading py-1 px-2 w-fit rounded-md bg-[#333333ce] hover:bg-[#333] hover:scale-110 transition-all duration-300 text-xl text-orange-50 hover:text-orange-400"
                  onClick={(e) => {
                    if (link.path.startsWith("#")) {
                      e.preventDefault();
                      const target = document.getElementById(
                        link.path.slice(1)
                      );
                      const navbarHeight =
                        document.querySelector("nav")?.offsetHeight || 0;

                      if (target) {
                        const targetPosition =
                          target.offsetTop - navbarHeight + 1;
                        window.scrollTo({
                          top: targetPosition,
                          behavior: "smooth",
                        });
                      }
                    }
                  }}
                >
                  {link.name}
                </NavLink>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* What Section */}
      <section
        id="what"
        className="relative flex flex-col gap-8 justify-center items-center p-16 bg-white shadow-sm"
      >
        {/* Section Heading */}
        <h1 className="text-4xl font-bold font-heading uppercase text-orange-400">
          GET CONNECTED FAST NOT ANY AVERAGE PLATFORM
        </h1>
        <p className="text-center font-bold font-body w-4/5 text-lg text-gray-700">
          Welcome to <span className="text-orange-400">TiffinCraft</span>, your
          ultimate destination for homemade food enthusiasts and culinary
          experts alike. Whether you're a passionate home cook or someone in
          search of authentic home-cooked meals,{" "}
          <span className="text-orange-400">TiffinCraft</span> unites food
          lovers from all walks of life.
        </p>

        {/* Features Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-8 w-5/6 relative z-10">
          {features.map((feature, index) => (
            <div
              key={index}
              className="flex flex-col border p-6 rounded-md shadow-md bg-blue-50"
            >
              <span className="flex items-center text-7xl">{feature.icon}</span>
              <h2 className="font-bold font-heading text-4xl py-2 text-gray-700">
                {feature.title}
              </h2>
              <p className="font-body text-lg text-gray-600">
                {feature.description}
              </p>
            </div>
          ))}
        </div>
      </section>

      {/* How It Works Section */}
      <section
        id="how"
        className="p-16 flex flex-col justify-center items-center bg-green-50"
      >
        {/* Section Heading */}
        <div className="w-3/4 text-start ml-2">
          <h1 className="text-4xl uppercase font-heading font-bold text-orange-400">
            How it Works
          </h1>
          <p className="text-lg font-body font-bold">
            Easy Steps to Get Started
          </p>
        </div>

        {/* Steps */}
        <div className="grid grid-cols-1 gap-12 w-3/4">
          {/* Step 1 */}
          <div className="grid md:grid-cols-2 gap-8 items-center">
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
                alt="Step 1: A person holding a phone"
                className="relative z-10 h-[500px] w-auto"
              />
            </div>
          </div>

          {/* Step 2 */}
          <div className="grid md:grid-cols-2 gap-8 items-center">
            <div className="flex justify-center relative order-last md:order-none">
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
          </div>

          {/* Step 3 */}
          <div className="grid md:grid-cols-2 gap-8 items-center">
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
        </div>
      </section>

      <section id="vendors" className="p-16">
        vendors
      </section>
      <section id="foods" className="p-16 relative">
        <Slider />
      </section>
    </>
  );
};
