import React from "react";
import { NavLink } from "react-router-dom";
import { GrGroup } from "react-icons/gr";
import { TbListSearch } from "react-icons/tb";
// import { MdOutlineQuestionMark, MdOutlineDoubleArrow } from "react-icons/md";
import { IoInfiniteSharp, IoBonfire } from "react-icons/io5";

import image_1 from "../images/step_1.png";
import image_2 from "../images/step_2.png";
import image_3 from "../images/step_3.png";
import image_11 from "../images/step_11.png";
import image_22 from "../images/step_22.png";
import image_33 from "../images/step_33.png";
import image_44 from "../images/step_44.webp";

import { VendorSlider } from "../components/VendorSlider";
import FoodGallery from "../components/FoodGallery";

// Hero Links
const heroLinks = [
  { name: "About TiffinCraft", path: "#about" },
  { name: "How It Works", path: "#how" },
  { name: "Meet the Vendors", path: "#vendors" },
  { name: "Delicious Dishes", path: "#foods" },
  { name: "Become a Seller", path: "#seller" },
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
        className="relative font-body h-[100vh] bg-hero-img bg-cover bg-center bg-no-repeat bg-fixed flex justify-center"
      >
        {/* Dark Overlay */}
        <div className="absolute inset-0 bg-[#333] opacity-50"></div>

        {/* Main Content */}
        <div className="w-SW relative z-10 flex flex-col xl:flex-row justify-center gap-20 items-center h-full p-8 text-white">
          <div className="w-full flex flex-col justify-center">
            {/* Headings */}
            <h1 className="font-heading text-9xl font-bold mb-6">Hungry!</h1>
            <h5 className="font-heading text-3xl font-bold mt-4 ml-2">
              What are you waiting for!
              <br />
              Eat delicious meals everyday.
            </h5>
          </div>
          {/* Hero Links */}
          <div className="w-full flex flex-wrap  gap-4 justify-start items-start">
            {heroLinks.map((link, index) => (
              <NavLink
                key={index}
                to={link.path}
                className="font-heading py-1 px-2 w-fit rounded-md bg-[#333333ce] hover:bg-[#333] hover:scale-110 transition-all duration-300 text-xl text-white hover:text-orange-400"
                onClick={(e) => {
                  if (link.path.startsWith("#")) {
                    e.preventDefault();
                    const target = document.getElementById(link.path.slice(1));
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
      </section>

      {/* About TiffinCraft Section */}
      <section
        id="about"
        className="relative flex flex-col gap-8 justify-center items-center p-16 bg-white shadow-sm"
      >
        <div className="w-SW text-start">
          {/* Section Heading */}
          <h1 className="text-4xl uppercase font-heading font-bold italic mb-2 text-orange-400">
            Discover TiffinCraft
          </h1>
          <p className="text-xl font-body font-bold italic">
            Where Every Meal is a Masterpiece
          </p>
          <p className="text-start font-bold font-body mt-4 text-lg text-gray-700">
            Welcome to <span className="text-orange-400">TiffinCraft</span>, the
            ultimate destination for homemade food enthusiasts and culinary
            experts alike. Whether you’re a passionate home cook eager to share
            your creations or someone seeking the comfort of authentic
            home-cooked meals,{" "}
            <span className="text-orange-400">TiffinCraft</span> brings together
            food lovers from all walks of life to celebrate the art of cooking
            and sharing
          </p>
        </div>

        {/* Features Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 w-SW relative z-10">
          {features.map((feature, index) => (
            <div
              key={index}
              className="flex flex-col border p-6 rounded-md shadow-md bg-green-50"
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
        className="p-16 flex flex-col gap-16 justify-center items-center bg-orange-50"
      >
        {/* Section Heading */}
        <div className="w-SW text-start ml-2">
          <h1 className="text-4xl uppercase font-heading font-bold italic mb-2 text-orange-400">
            How TiffinCraft Works
          </h1>
          <p className="text-lg font-body font-bold italic">
            Bringing Home-Cooked Goodness to Your Doorstep
          </p>
        </div>

        {/* Steps */}
        <div className="w-SW grid grid-cols-1 gap-12">
          {/* Step 1 */}
          <div className="grid lg:grid-cols-2 gap-24 items-center">
            <div className="flex flex-col justify-center">
              <span className="font-heading font-extrabold text-9xl text-orange-200 hover:text-orange-300 transition-all duration-500">
                01
              </span>
              <h1 className="font-heading font-bold text-4xl my-4 ml-2 text-orange-300">
                Find Your Vendor
              </h1>
              <p className="font-body italic text-lg text-gray-700 ml-2">
                Browse through our trusted vendors and pick the one that matches
                your requirements. It's easy to{" "}
                <NavLink to={"/regrister"} className="text-blue-400 font-bold">
                  sign up
                </NavLink>{" "}
                and create your account.
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
          <div className="grid lg:grid-cols-2 gap-24 items-center">
            <div className="flex justify-center relative order-last lg:order-none">
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
              <p className="font-body italic text-lg text-gray-700 ml-2">
                Communicate with the vendor to design your ideal meal plan. You
                can tailor it to your preferences and needs.
              </p>
            </div>
          </div>

          {/* Step 3 */}
          <div className="grid lg:grid-cols-2 gap-24 items-center">
            <div className="flex flex-col justify-center">
              <span className="font-heading font-extrabold text-9xl text-green-200 hover:text-green-400 transition-all duration-500">
                03
              </span>
              <h1 className="font-heading font-bold text-4xl my-4 ml-2 text-green-400">
                Enjoy Hassle-Free Meals
              </h1>
              <p className="font-body italic text-lg text-gray-700 ml-2">
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

      {/* Become a Seller Section */}
      <section
        id="how"
        className="p-16 flex flex-col gap-16 justify-center items-center bg-white"
      >
        {/* Section Heading */}
        <div className="w-SW text-start ml-2">
          <h1 className="text-4xl uppercase font-heading font-bold italic mb-2 text-orange-400">
            Partner with TiffinCraft
          </h1>
          <p className="text-lg font-body font-bold italic">
            Share your culinary talent, reach more customers, and grow your
            business effortlessly. Signing up is quick and easy!
          </p>
        </div>

        {/* Steps */}
        <div className="w-SW grid grid-cols-1 gap-12">
          {/* Step 1 */}
          <div className="grid lg:grid-cols-2 gap-24 items-center">
            <div className="flex justify-center relative">
              <img
                src={image_11}
                alt="Step 1: A person holding a phone"
                className="relative z-10 h-[500px] w-auto"
              />
            </div>
            <div className="flex flex-col justify-center">
              <span className="font-heading font-extrabold text-9xl text-orange-200 hover:text-orange-300 transition-all duration-500">
                01
              </span>
              <h1 className="font-heading font-bold text-4xl my-4 ml-2 text-orange-300">
                Regrister as a Seller
              </h1>
              <p className="font-body italic text-lg text-gray-700 ml-2">
                Step into a world of endless opportunities. Become part of our
                thriving community of home chefs and turn your passion for
                cooking into a rewarding journey.
              </p>
            </div>
          </div>

          {/* Step 2 */}
          <div className="grid lg:grid-cols-2 gap-24 items-center">
            <div className="flex flex-col justify-center">
              <span className="font-heading font-extrabold text-9xl text-[#97a48598] hover:text-[#97A485] transition-all duration-500">
                02
              </span>
              <h1 className="font-heading font-bold text-4xl my-4 ml-2 text-[#97A485]">
                List Your Dishes
              </h1>
              <p className="font-body italic text-lg text-gray-700 ml-2">
                Share your culinary masterpieces with the world. Create a
                personalized menu, set your prices, and make your mark with your
                signature dishes.
              </p>
            </div>
            <div className="flex justify-center relative order-last lg:order-none">
              <img
                src={image_22}
                alt="Step 2: A person talking on the phone with the vendors"
                className="relative z-10 h-[500px] w-auto"
              />
            </div>
          </div>

          {/* Step 3 */}
          <div className="grid lg:grid-cols-2 gap-24 items-center">
            <div className="flex justify-center relative">
              <img
                src={image_33}
                alt="Step 3: A person delivering food to the customer"
                className="relative z-10 h-[500px] w-auto"
              />
            </div>
            <div className="flex flex-col justify-center">
              <span className="font-heading font-extrabold text-9xl text-[#ffa07aa1] hover:text-[#FFA07A] transition-all duration-500">
                03
              </span>
              <h1 className="font-heading font-bold text-4xl my-4 ml-2 text-[#FFA07A]">
                Connect with Customers
              </h1>
              <p className="font-body italic text-lg text-gray-700 ml-2">
                Build lasting connections with food lovers who appreciate the
                magic of home-cooked meals. Inspire their taste buds with every
                bite.
              </p>
            </div>
          </div>

          {/* Step 4 */}
          <div className="grid lg:grid-cols-2 gap-24 items-center">
            <div className="flex flex-col justify-center">
              <span className="font-heading font-extrabold text-9xl text-blue-200 hover:text-blue-400 transition-all duration-500">
                04
              </span>
              <h1 className="font-heading font-bold text-4xl my-4 ml-2 text-blue-400">
                Get Paid
              </h1>
              <p className="font-body italic text-lg text-gray-700 ml-2">
                Enjoy a seamless payment experience while you focus on
                delighting your customers with exceptional meals.
              </p>
            </div>
            <div className="flex justify-center relative">
              <img
                src={image_44}
                alt="Step 3: A person delivering food to the customer"
                className="relative z-10 h-[500px] w-auto"
              />
            </div>
          </div>
        </div>
      </section>

      {/* Meet Our Vendors  */}
      <section
        id="vendors"
        className="p-16 bg-orange-50 flex flex-col items-center relative"
      >
        {/* Section Heading */}
        <div className="w-SW text-start mb-8">
          <h1 className="text-4xl uppercase font-heading font-bold italic mb-2 text-orange-400">
            Meet Our Vendors
          </h1>
          <p className="text-lg font-body font-bold italic">
            Connecting You with Passionate Home Chefs
          </p>
        </div>
        {/* Vendor Slider */}
        <VendorSlider />
      </section>

      {/* Discover Delicious Creations  */}
      <section
        id="foods"
        className="p-16 flex flex-col items-center bg-white relative"
      >
        <div className="w-SW text-start mb-8">
          <h1 className="text-4xl uppercase font-heading font-bold italic mb-2 text-orange-400">
            Discover Delicious Creations
          </h1>
          <p className="text-lg font-body font-bold italic">
            Every Dish Tells a Story
          </p>
        </div>
        <FoodGallery />
      </section>
    </>
  );
};
