import React, { useState } from "react";
import { NavLink } from "react-router-dom";
import {
  MdHome,
  MdMenuBook,
  MdGroups2,
  MdInfo,
  MdSupport,
} from "react-icons/md";
import { HiOutlineChevronDown } from "react-icons/hi";
import { RiSearchEyeLine } from "react-icons/ri";

import logo from "../images/TiffinCraft.png";

const navLinks = [
  {
    name: "Home",
    path: "/",
    icon: <MdHome />,
  },
  {
    name: "Browse",
    icon: <RiSearchEyeLine />,
    dropdown: [
      {
        name: "Vendors",
        path: "/vendors",
        icon: <MdGroups2 />,
      },
      {
        name: "Menus",
        path: "/menu",
        icon: <MdMenuBook />,
      },
    ],
  },
  {
    name: "About",
    path: "/about",
    icon: <MdInfo />,
  },
  {
    name: "Support",
    path: "/contact",
    icon: <MdSupport />,
  },
];

const Header = ({ activeSection }) => {
  const [dropdownOpen, setDropdownOpen] = useState(false);

  // Function to handle dropdown toggle
  const handleDropdownToggle = () => {
    setDropdownOpen((prev) => !prev);
  };

  return (
    <nav className="flex flex-row justify-between items-center px-8 md:px-12 lg:px-24 shadow-md fixed w-full z-50 bg-white">
      {/* Logo */}
      <img className="h-[60px]" src={logo} alt="TiffinCraft Logo" />

      {/* Navigation Links */}
      <div className="flex flex-row gap-8">
        {navLinks.map((links, index) => (
          <div key={index} className="relative">
            {!links.dropdown ? (
              <NavLink
                to={links.path}
                className="flex items-center gap-2 font-heading font-bold text-md [&.active]:text-orange-400 hover:text-orange-400 transition-colors duration-500"
              >
                {links.icon}
                {links.name}
              </NavLink>
            ) : (
              // Parent link with dropdown
              <div className="flex flex-col">
                <button
                  onClick={handleDropdownToggle}
                  className="flex items-center gap-2 font-heading font-bold text-md hover:text-orange-400 relative transition-colors duration-500"
                >
                  {links.icon}
                  {links.name}
                  <HiOutlineChevronDown
                    className={`text-sm transition-transform duration-500 ${
                      dropdownOpen ? "rotate-180" : "rotate-0"
                    }`}
                  />
                </button>

                {/* Dropdown Menu */}
                {dropdownOpen && (
                  <div className="absolute top-full left-0 mt-2 w-40 border bg-orange-50 shadow-lg rounded-md z-50">
                    {links.dropdown.map((subLink, subIndex) => (
                      <NavLink
                        key={subIndex}
                        to={subLink.path}
                        className="font-heading font-bold px-4 py-2 hover:text-orange-400 active:text-orange-400 hover:bg-gray-100 rounded-md flex items-center gap-2 transition-colors duration-500"
                      >
                        {subLink.icon} {subLink.name}
                      </NavLink>
                    ))}
                  </div>
                )}
              </div>
            )}
          </div>
        ))}
      </div>
    </nav>
  );
};

export default Header;
