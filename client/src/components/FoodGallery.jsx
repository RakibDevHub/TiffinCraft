import React from "react";
import { BiHeart, BiSolidOffer } from "react-icons/bi";
import { FaOpencart } from "react-icons/fa";
import { MdStore } from "react-icons/md";

import imge from "../images/hero.jpeg";

const foodItems = [
  {
    id: 1,
    content: "Content 1",
    text: "Hello this is content 1",
    bgColor: "bg-blue-300",
  },
  { id: 2, content: "Content 2", text: "", bgColor: "bg-green-300" },
  { id: 3, content: "Content 3", text: "", bgColor: "bg-red-300" },
  { id: 4, content: "Content 4", text: "", bgColor: "bg-yellow-300" },
  {
    id: 5,
    content: "Content 5",
    text: "Lorem ipsum dolor sit, amet consectetur adipisicing elit. Quo maxime iste obcaecati maiores fugit, laudantium voluptas tempore accusantium aliquid minima amet expedita impedit odit vero animi ut dicta culpa vel!",

    bgColor: "bg-purple-300",
  },
  { id: 6, content: "Content 6", text: "", bgColor: "bg-pink-300" },
  { id: 7, content: "Content 7", text: "", bgColor: "bg-teal-300" },
  { id: 8, content: "Content 8", text: "", bgColor: "bg-orange-300" },
  { id: 9, content: "Content 9", text: "", bgColor: "bg-indigo-300" },
  { id: 10, content: "Content 10", text: "", bgColor: "bg-gray-300" },
  { id: 11, content: "Content 11", text: "", bgColor: "bg-brown-500" },
  { id: 12, content: "Content 12", text: "", bgColor: "bg-lime-300" },
];

const FoodGallery = () => {
  return (
    <div className="grid sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 w-3/4">
      {/* Loop through the foodItems array to render each item */}
      {itemSlider.map((item, index) => (
        <div
          key={index}
          className="flex flex-col gap-1 bg-green-50 p-2 border-2 rounded-md font-body"
        >
          <img src={item.itemImage} alt={item.itemName} className="" />
          <div className="py-2 flex flex-col">
            <div className="flex flex-row justify-between items-center mb-2">
              <span className="flex flex-row items-center gap-1 bg-green-400 text-white uppercase px-2 rounded-md text-sm">
                {item.itemOffer ? (
                  <>
                    <BiSolidOffer /> Up to {item.itemOffer}% off
                  </>
                ) : (
                  <></>
                )}
              </span>

              <BiHeart className="text-orange-400 text-lg" />
            </div>
            <h4 className="font-heading font-bold text-lg">{item.itemName}</h4>
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
  );
};

export default FoodGallery;

const itemSlider = [
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
];
