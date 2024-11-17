import React from "react";

import { Swiper, SwiperSlide } from "swiper/react";
import { Navigation, Pagination } from "swiper/modules";
import "swiper/swiper-bundle.css";

import imge from "../images/hero.jpeg";
import { Link } from "react-router-dom";

export const Slider = () => {
  return (
    <div className="mt-24 px-16">
      <Swiper
        slidesPerView={1}
        spaceBetween={20}
        pagination={{
          clickable: true,
        }}
        navigation={true}
        rewind={true}
        modules={[Pagination, Navigation]}
        className="pb-10"
        breakpoints={{
          640: {
            slidesPerView: 1,
          },
          786: {
            slidesPerView: 2,
          },
          1024: {
            slidesPerView: 4,
          },
          1280: {
            slidesPerView: 5,
          },
        }}
      >
        {sliderData.map((data, index) => (
          <SwiperSlide
            className=" border-2 border-[#e2e8f0] p-2 shadow-md rounded-md"
            key={index}
          >
            {/* <div className=""> */}
            <img src={data.img} alt={data.name} className="" />
            {/* </div> */}
            <div className="py-2">
              <h4 className="font-heading font-bold text-lg">{data.name}</h4>
              <p className="font-body leading-tight">{data.content}</p>
              <Link to="/" className="">
                Read more.
              </Link>
            </div>
          </SwiperSlide>
        ))}
      </Swiper>
    </div>
  );
};

const sliderData = [
  {
    name: `Ria Kitchen`,
    img: imge,
    content: `You may combine any of the options above.
              For example, to get a specific image that is grayscale and blurred.`,
  },
  {
    name: `Ria Kitchen`,
    img: imge,
    content: `You may combine any of the options above.
              For example, to get a specific image that is grayscale and blurred.`,
  },
  {
    name: `Ria Kitchen`,
    img: imge,
    content: `You may combine any of the options above.
              For example, to get a specific image that is grayscale and blurred.`,
  },
  {
    name: `Ria Kitchen`,
    img: imge,
    content: `You may combine any of the options above.
              For example, to get a specific image that is grayscale and blurred.`,
  },
  {
    name: `Ria Kitchen`,
    img: imge,
    content: `You may combine any of the options above.
              For example, to get a specific image that is grayscale and blurred.`,
  },
  {
    name: `Ria Kitchen`,
    img: imge,
    content: `You may combine any of the options above.
              For example, to get a specific image that is grayscale and blurred.`,
  },
  {
    name: `Ria Kitchen`,
    img: imge,
    content: `You may combine any of the options above.
              For example, to get a specific image that is grayscale and blurred.`,
  },
  {
    name: `Ria Kitchen`,
    img: imge,
    content: `You may combine any of the options above.
              For example, to get a specific image that is grayscale and blurred.`,
  },
  {
    name: `Ria Kitchen`,
    img: imge,
    content: `You may combine any of the options above.
              For example, to get a specific image that is grayscale and blurred.`,
  },
  {
    name: `Ria Kitchen`,
    img: imge,
    content: `You may combine any of the options above.
              For example, to get a specific image that is grayscale and blurred.`,
  },
];
