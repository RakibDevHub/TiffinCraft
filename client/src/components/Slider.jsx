import React, { useRef } from "react";
import { Swiper, SwiperSlide } from "swiper/react";
import { Navigation, Pagination } from "swiper/modules";
import "swiper/swiper-bundle.css";

import { BiSolidOffer, BiHeart } from "react-icons/bi";
import { MdStore, MdOutlineLocalGroceryStore } from "react-icons/md";
import { HiOutlineArrowLeft, HiOutlineArrowRight } from "react-icons/hi";

import imge from "../images/hero.jpeg";

export const Slider = () => {
  const prevRef = useRef(null);
  const nextRef = useRef(null);

  return (
    <div className="mt-24 px-16 relative">
      <Swiper
        slidesPerView={1}
        spaceBetween={20}
        pagination={{
          clickable: true,
          el: ".custom-pagination", // Use custom pagination class
        }}
        navigation={{
          prevEl: prevRef.current,
          nextEl: nextRef.current,
        }}
        onBeforeInit={(swiper) => {
          swiper.params.navigation.prevEl = prevRef.current;
          swiper.params.navigation.nextEl = nextRef.current;
        }}
        rewind={true}
        modules={[Pagination, Navigation]}
        className="mySwiper pb-14"
        breakpoints={{
          640: {
            slidesPerView: 1,
          },
          768: {
            slidesPerView: 2,
          },
          1024: {
            slidesPerView: 3,
          },
          1280: {
            slidesPerView: 4,
          },
        }}
      >
        {itemSlider.map((item, index) => (
          <SwiperSlide
            className="border-2 border-[#e2e8f0] p-2 shadow-md rounded-md font-body"
            key={index}
          >
            <img src={item.itemImage} alt={item.itemName} className="" />
            <div className="py-2 flex flex-col">
              <div className="flex flex-row justify-between items-center mb-2">
                <span className="flex flex-row items-center gap-1 bg-orange-400 text-white px-2 rounded-md text-sm">
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
              <h4 className="font-heading font-bold text-lg">
                {item.itemName}
              </h4>
              <p className="font-body leading-tight py-2">{item.itemDetails}</p>
              <span>{item.itemRating}</span>
              <span className="flex flex-row items-center gap-1 font-bold text-base">
                <MdStore /> {item.itemVendor}
              </span>
              <div className="flex flex-row justify-between items-center mt-2">
                <span className="text-3xl text-orange-400">
                  ${item.itemPrice}
                </span>
                <button className="flex flex-row items-center justify-center gap-1 font-bold bg-[#6B7280] text-white py-1 px-2 rounded-md hover:bg-[#555]">
                  <MdOutlineLocalGroceryStore /> Order Now
                </button>
              </div>
            </div>
          </SwiperSlide>
        ))}
      </Swiper>

      {/* Custom Navigation and Pagination Controls */}
      <div className="flex justify-center items-center mt-4 absolute bottom-2 left-0 right-0 z-50 px-16 space-x-4">
        <button
          ref={prevRef}
          className="bg-gray-200 p-2 rounded-full hover:bg-gray-300"
        >
          <HiOutlineArrowLeft className="text-2xl text-gray-700" />
        </button>

        {/* Custom Pagination Dots */}
        <div className="custom-pagination flex justify-center gap-2"></div>

        <button
          ref={nextRef}
          className="bg-gray-200 p-2 rounded-full hover:bg-gray-300"
        >
          <HiOutlineArrowRight className="text-2xl text-gray-700" />
        </button>
      </div>
    </div>
  );
};

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
];
