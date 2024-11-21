/** @type {import('tailwindcss').Config} */
const plugin = require("tailwindcss/plugin");

module.exports = {
  content: ["./src/**/*.{html,js,jsx}"],
  theme: {
    extend: {
      width: {
        SW: "80%",
      },

      // Custom Fonts
      fontFamily: {
        heading: ["Poppins", "sans-serif"],
        body: ["Nunito", "sans-serif"],
      },

      // Custom Background Images
      backgroundImage: {
        "hero-img": "url('./images/hero.jpeg')",
        gradient:
          "linear-gradient(112deg, rgba(213, 92, 0, 1) 0%, rgba(245, 121, 59, 1) 41%, rgba(245, 183, 59, 1) 80%)",
      },
    },
  },

  // Plugins
  plugins: [
    plugin(function ({ addVariant }) {
      // Custom variant for active state
      addVariant("current", "&.active");
    }),
  ],
};
