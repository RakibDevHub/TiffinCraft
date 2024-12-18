import React, { useRef } from "react";
import { Swiper, SwiperSlide } from "swiper/react";
import { Navigation, Pagination } from "swiper/modules";
import "swiper/swiper-bundle.css";

import { BiSolidOffer, BiHeart } from "react-icons/bi";
import { MdStore } from "react-icons/md";
import { HiOutlineArrowLeft, HiOutlineArrowRight } from "react-icons/hi";
import { FaOpencart } from "react-icons/fa";

import imge from "../images/hero.jpeg";

export const VendorSlider = () => {
  const prevRef = useRef(null);
  const nextRef = useRef(null);

  return (
    <>
      <Swiper
        slidesPerView={1}
        slidesPerGroup={1}
        spaceBetween={20}
        pagination={{
          clickable: true,
          el: ".custom-pagination",
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
        className="mySwiper w-SW p-4"
        breakpoints={{
          640: {
            slidesPerView: 1,
            slidesPerGroup: 1,
          },
          768: {
            slidesPerView: 2,
            slidesPerGroup: 2,
          },
          1024: {
            slidesPerView: 3,
            slidesPerGroup: 3,
          },
          1280: {
            slidesPerView: 4,
            slidesPerGroup: 4,
          },
        }}
      >
        {itemSlider.map((item, index) => (
          <SwiperSlide
            className="border-2 border-[#e2e8f0] p-2 shadow-md rounded-md font-body bg-green-50 hover:scale-105"
            key={index}
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
          </SwiperSlide>
        ))}
      </Swiper>

      {/* Custom Navigation and Pagination Controls */}
      <div className="w-SW flex justify-center items-center mt-4 bottom-2 z-50 space-x-4">
        <button
          ref={prevRef}
          className="bg-[#113592] p-2 rounded-full hover:bg-[#002379]"
        >
          <HiOutlineArrowLeft className="text-2xl text-white" />
        </button>

        {/* Custom Pagination Dots */}
        <div className="custom-pagination flex justify-center gap-2"></div>

        <button
          ref={nextRef}
          className="bg-[#113592] p-2 rounded-full hover:bg-[#002379]"
        >
          <HiOutlineArrowRight className="text-2xl text-white" />
        </button>
      </div>
    </>
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
