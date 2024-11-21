import React, { useState } from "react";

import { MdStore } from "react-icons/md";
import { FaOpencart } from "react-icons/fa";
import { BiSolidOffer } from "react-icons/bi";
import { IoMdHeart, IoMdHeartEmpty } from "react-icons/io";

import imge from "../images/hero.jpeg";

const itemGallery = [
  {
    itemImage: imge,
    itemName: "Bhuna Khichuri",
    itemDetails: "You may combine any of the options above.",
    itemPrice: "80",
    itemOffer: "20",
    itemRating: "No Rating Yet",
    itemVendor: "Kamal Kitchen",
  },
  {
    itemImage: imge,
    itemName: "Bhuna Khichuri",
    itemDetails: "You may combine any of the options above.",
    itemPrice: "80",
    itemOffer: "20",
    itemRating: "No Rating Yet",
    itemVendor: "Kamal Kitchen",
  },
  {
    itemImage: imge,
    itemName: "Bhuna Khichuri",
    itemDetails: "You may combine any of the options above.",
    itemPrice: "80",
    itemOffer: "",
    itemRating: "No Rating Yet",
    itemVendor: "Kamal Kitchen",
  },
  {
    itemImage: imge,
    itemName: "Bhuna Khichuri",
    itemDetails: "You may combine any of the options above.",
    itemPrice: "80",
    itemOffer: "20",
    itemRating: "No Rating Yet",
    itemVendor: "Kamal Kitchen",
  },
  {
    itemImage: imge,
    itemName: "Bhuna Khichuri",
    itemDetails: "You may combine any of the options above.",
    itemPrice: "80",
    itemOffer: "20",
    itemRating: "No Rating Yet",
    itemVendor: "Kamal Kitchen",
  },
  {
    itemImage: imge,
    itemName: "Bhuna Khichuri",
    itemDetails: "You may combine any of the options above.",
    itemPrice: "80",
    itemOffer: "20",
    itemRating: "No Rating Yet",
    itemVendor: "Kamal Kitchen",
  },
  {
    itemImage: imge,
    itemName: "Bhuna Khichuri",
    itemDetails: "You may combine any of the options above.",
    itemPrice: "80",
    itemOffer: "20",
    itemRating: "No Rating Yet",
    itemVendor: "Kamal Kitchen",
  },
  {
    itemImage: imge,
    itemName: "Bhuna Khichuri",
    itemDetails: "You may combine any of the options above.",
    itemPrice: "80",
    itemOffer: "20",
    itemRating: "No Rating Yet",
    itemVendor: "Kamal Kitchen",
  },
  {
    itemImage: imge,
    itemName: "Bhuna Khichuri",
    itemDetails: "You may combine any of the options above.",
    itemPrice: "80",
    itemOffer: "20",
    itemRating: "No Rating Yet",
    itemVendor: "Kamal Kitchen",
  },
];

const FoodGallery = () => {
  const itemsPerPage = 4; // Number of items to display per page
  const [currentPage, setCurrentPage] = useState(1);

  const totalPages = Math.ceil(itemGallery.length / itemsPerPage);

  // Calculate the items to display based on the current page
  const currentItems = itemGallery.slice(
    (currentPage - 1) * itemsPerPage,
    currentPage * itemsPerPage
  );

  const handlePageClick = (page) => {
    setCurrentPage(page);
  };

  const handleNext = () => {
    if (currentPage < totalPages) setCurrentPage(currentPage + 1);
  };

  const handlePrevious = () => {
    if (currentPage > 1) setCurrentPage(currentPage - 1);
  };

  return (
    <div className="flex flex-col items-center gap-6">
      {/* Food Gallery Grid */}
      <div className="grid sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 w-SW">
        {currentItems.map((item, index) => (
          <div
            key={index}
            className="flex flex-col gap-1 bg-green-50 p-2 border-2 rounded-md font-body hover:scale-105"
          >
            <img src={item.itemImage} alt={item.itemName} className="" />
            <div className="py-2 flex flex-col">
              <div className="flex flex-row justify-between items-center mb-2">
                <span className="flex flex-row items-center gap-1 bg-green-400 text-white uppercase px-2 rounded-md text-sm">
                  {item.itemOffer ? (
                    <>
                      <BiSolidOffer /> Up to {item.itemOffer}% off
                    </>
                  ) : null}
                </span>

                <div className="relative group cursor-pointer flex flex-col items-center">
                  {/* Icon Container */}
                  <div className="relative">
                    {/* Empty Heart Icon */}
                    <IoMdHeartEmpty className="text-orange-400 text-xl font-bold group-hover:hidden transition-transform duration-300" />
                    {/* Filled Heart Icon */}
                    <IoMdHeart className="text-orange-400 text-xl font-bold hidden group-hover:block transition-transform duration-300" />
                  </div>
                  {/* Hover Text */}
                  <span className="w-max bg-[#555] absolute mt-4 px-2 rounded-md shadow-md text-sm text-white opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                    Added to Favorite
                  </span>
                </div>
              </div>
              <h4 className="font-heading font-bold text-lg">
                {item.itemName}
              </h4>
              <p className="font-body leading-tight py-2">{item.itemDetails}</p>
              <span>{item.itemRating}</span>
              <span className="flex flex-row items-center gap-1 py-2 font-bold text-base">
                <MdStore /> {item.itemVendor}
              </span>
              <div className="flex flex-row justify-between items-center mt-2">
                <span className="text-3xl text-orange-400">
                  ${item.itemPrice}
                </span>
                <button className="flex flex-row items-center justify-center gap-1 font-bold bg-blue-400 text-white py-1 px-2 rounded-md hover:bg-blue-500">
                  Order Now <FaOpencart />
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Pagination Controls */}
      {/* <div className="flex justify-between w-1/2">
        <button
          onClick={handlePrevious}
          disabled={currentPage === 1}
          className={`px-4 py-2 rounded-md font-bold ${
            currentPage === 1
              ? "bg-gray-300 cursor-not-allowed"
              : "bg-blue-400 text-white hover:bg-blue-500"
          }`}
        >
          Previous
        </button>
        <span className="font-bold">
          Page {currentPage} of {totalPages}
        </span>
        <button
          onClick={handleNext}
          disabled={currentPage === totalPages}
          className={`px-4 py-2 rounded-md font-bold ${
            currentPage === totalPages
              ? "bg-gray-300 cursor-not-allowed"
              : "bg-blue-400 text-white hover:bg-blue-500"
          }`}
        >
          Next
        </button>
      </div> */}
      <div className="flex items-center gap-2 mt-4 font-bold font-body">
        <button
          onClick={handlePrevious}
          disabled={currentPage === 1}
          className={`px-4 py-2 rounded-md border ${
            currentPage === 1
              ? "bg-gray-300 text-gray-500 cursor-not-allowed"
              : "bg-white border-orange-400 text-orange-400 hover:bg-orange-400 hover:text-white"
          }`}
        >
          Previous
        </button>
        {Array.from({ length: totalPages }, (_, index) => (
          <button
            key={index + 1}
            onClick={() => handlePageClick(index + 1)}
            className={`px-4 py-2 rounded-md border ${
              currentPage === index + 1
                ? "bg-orange-400 text-white"
                : "bg-white border-orange-400 text-orange-400 hover:bg-orange-400 hover:text-white"
            }`}
          >
            {index + 1}
          </button>
        ))}
        <button
          onClick={handleNext}
          disabled={currentPage === totalPages}
          className={`px-4 py-2 rounded-md border ${
            currentPage === totalPages
              ? "bg-gray-300 text-gray-500 cursor-not-allowed"
              : "bg-white border-orange-400 text-orange-400 hover:bg-orange-400 hover:text-white"
          }`}
        >
          Next
        </button>
      </div>
    </div>
  );
};

export default FoodGallery;
