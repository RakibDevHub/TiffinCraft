import React from "react";
import { NavLink } from "react-router-dom";

// import { FaHome, FaUsers } from "react-icons/fa";
import {
  MdHome,
  MdMenuBook,
  MdGroups2,
  MdInfo,
  MdSupport,
} from "react-icons/md";

import logo from "../images/TiffinCraft.png";

const navLinks = [
  {
    name: "Home",
    path: "/",
    icon: <MdHome />,
  },
  {
    name: "Browse Vendors",
    path: "/vendors",
    icon: <MdGroups2 />,
  },
  {
    name: "Browes Menus",
    path: "/menu",
    icon: <MdMenuBook />,
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

const Header = () => {
  return (
    <nav className="flex flex-row justify-between items-center px-8 md:px-12 lg:px-24 shadow-md">
      <img className="h-[60px]" src={logo} alt="TiffinCraft Logo" />
      <div className="flex flex-row gap-4">
        {navLinks.map((links, index) => (
          <NavLink
            key={index}
            to={links.path}
            className="flex flex-row justify-between items-center gap-2 py-1 px-4 [&.active]:shadow-md hover:shadow-md rounded-3xl"
          >
            {links.icon}
            {links.name}
          </NavLink>
        ))}
      </div>
    </nav>
  );
};

export default Header;
