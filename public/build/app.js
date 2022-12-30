(self["webpackChunkgenerateur"] = self["webpackChunkgenerateur"] || []).push([["app"],{

/***/ "./assets/app.js":
/*!***********************!*\
  !*** ./assets/app.js ***!
  \***********************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _styles_app_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./styles/app.scss */ "./assets/styles/app.scss");
/* harmony import */ var bootstrap__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! bootstrap */ "./node_modules/bootstrap/dist/js/bootstrap.esm.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _javascript_custom_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./javascript/custom.js */ "./assets/javascript/custom.js");
/* harmony import */ var bs_custom_file_input__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! bs-custom-file-input */ "./node_modules/bs-custom-file-input/dist/bs-custom-file-input.js");
/* harmony import */ var bs_custom_file_input__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(bs_custom_file_input__WEBPACK_IMPORTED_MODULE_4__);
// assets/app.js

/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */
// any CSS you import will output into a single css file (app.css in this case)
 // Need jQuery? Install it with "yarn add jquery", then uncomment to import it.
// import $ from 'jquery';






console.log('Hello Webpack Encore! Edit me in assets/app.js');
bs_custom_file_input__WEBPACK_IMPORTED_MODULE_4___default().init();
jquery__WEBPACK_IMPORTED_MODULE_2___default()(document).ready(function () {
  jquery__WEBPACK_IMPORTED_MODULE_2___default()('.login-info-box').fadeOut();
  jquery__WEBPACK_IMPORTED_MODULE_2___default()('.login-show').addClass('show-log-panel');
});
jquery__WEBPACK_IMPORTED_MODULE_2___default()('.login-reg-panel input[type="radio"]').on('change', function () {
  if (jquery__WEBPACK_IMPORTED_MODULE_2___default()('#log-login-show').is(':checked')) {
    jquery__WEBPACK_IMPORTED_MODULE_2___default()('.register-info-box').fadeOut();
    jquery__WEBPACK_IMPORTED_MODULE_2___default()('.login-info-box').fadeIn();
    jquery__WEBPACK_IMPORTED_MODULE_2___default()('.white-panel').addClass('right-log');
    jquery__WEBPACK_IMPORTED_MODULE_2___default()('.register-show').addClass('show-log-panel');
    jquery__WEBPACK_IMPORTED_MODULE_2___default()('.login-show').removeClass('show-log-panel');
  } else if (jquery__WEBPACK_IMPORTED_MODULE_2___default()('#log-reg-show').is(':checked')) {
    jquery__WEBPACK_IMPORTED_MODULE_2___default()('.register-info-box').fadeIn();
    jquery__WEBPACK_IMPORTED_MODULE_2___default()('.login-info-box').fadeOut();
    jquery__WEBPACK_IMPORTED_MODULE_2___default()('.white-panel').removeClass('right-log');
    jquery__WEBPACK_IMPORTED_MODULE_2___default()('.login-show').addClass('show-log-panel');
    jquery__WEBPACK_IMPORTED_MODULE_2___default()('.register-show').removeClass('show-log-panel');
  }
});

/***/ }),

/***/ "./assets/javascript/custom.js":
/*!*************************************!*\
  !*** ./assets/javascript/custom.js ***!
  \*************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var core_js_modules_es_parse_float_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.parse-float.js */ "./node_modules/core-js/modules/es.parse-float.js");
/* harmony import */ var core_js_modules_es_parse_float_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_parse_float_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_number_to_fixed_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.number.to-fixed.js */ "./node_modules/core-js/modules/es.number.to-fixed.js");
/* harmony import */ var core_js_modules_es_number_to_fixed_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_to_fixed_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_2__);



__webpack_require__(/*! core-js/modules/es.array.find.js */ "./node_modules/core-js/modules/es.array.find.js");


jquery__WEBPACK_IMPORTED_MODULE_2___default()(document).ready(function () {
  var current_fs, next_fs, previous_fs; //fieldsets

  var opacity;
  var current = 1;
  var steps = jquery__WEBPACK_IMPORTED_MODULE_2___default()("fieldset").length;
  setProgressBar(current);
  jquery__WEBPACK_IMPORTED_MODULE_2___default()(".next").click(function () {
    current_fs = jquery__WEBPACK_IMPORTED_MODULE_2___default()(this).parent();
    next_fs = jquery__WEBPACK_IMPORTED_MODULE_2___default()(this).parent().next(); //Add Class Active

    jquery__WEBPACK_IMPORTED_MODULE_2___default()("#progressbar li").eq(jquery__WEBPACK_IMPORTED_MODULE_2___default()("fieldset").index(next_fs)).addClass("active"); //show the next fieldset

    next_fs.show(); //hide the current fieldset with style

    current_fs.animate({
      opacity: 0
    }, {
      step: function step(now) {
        // for making fielset appear animation
        opacity = 1 - now;
        current_fs.css({
          'display': 'none',
          'position': 'relative'
        });
        next_fs.css({
          'opacity': opacity
        });
      },
      duration: 500
    });
    setProgressBar(++current);
  });
  jquery__WEBPACK_IMPORTED_MODULE_2___default()(".previous").click(function () {
    current_fs = jquery__WEBPACK_IMPORTED_MODULE_2___default()(this).parent();
    previous_fs = jquery__WEBPACK_IMPORTED_MODULE_2___default()(this).parent().prev(); //Remove class active

    jquery__WEBPACK_IMPORTED_MODULE_2___default()("#progressbar li").eq(jquery__WEBPACK_IMPORTED_MODULE_2___default()("fieldset").index(current_fs)).removeClass("active"); //show the previous fieldset

    previous_fs.show(); //hide the current fieldset with style

    current_fs.animate({
      opacity: 0
    }, {
      step: function step(now) {
        // for making fielset appear animation
        opacity = 1 - now;
        current_fs.css({
          'display': 'none',
          'position': 'relative'
        });
        previous_fs.css({
          'opacity': opacity
        });
      },
      duration: 500
    });
    setProgressBar(--current);
  });

  function setProgressBar(curStep) {
    var percent = parseFloat(100 / steps) * curStep;
    percent = percent.toFixed();
    jquery__WEBPACK_IMPORTED_MODULE_2___default()(".progress-bar").css("width", percent + "%");
  }

  jquery__WEBPACK_IMPORTED_MODULE_2___default()(".submit").click(function () {
    return false;
  });
});

/***/ }),

/***/ "./assets/styles/app.scss":
/*!********************************!*\
  !*** ./assets/styles/app.scss ***!
  \********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ "use strict";
/******/ 
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ __webpack_require__.O(0, ["vendors-node_modules_bootstrap_dist_js_bootstrap_esm_js-node_modules_bs-custom-file-input_dis-367caa"], () => (__webpack_exec__("./assets/app.js")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly9nZW5lcmF0ZXVyLy4vYXNzZXRzL2FwcC5qcyIsIndlYnBhY2s6Ly9nZW5lcmF0ZXVyLy4vYXNzZXRzL2phdmFzY3JpcHQvY3VzdG9tLmpzIiwid2VicGFjazovL2dlbmVyYXRldXIvLi9hc3NldHMvc3R5bGVzL2FwcC5zY3NzIl0sIm5hbWVzIjpbImNvbnNvbGUiLCJsb2ciLCJic0N1c3RvbUZpbGVJbnB1dCIsIiQiLCJkb2N1bWVudCIsInJlYWR5IiwiZmFkZU91dCIsImFkZENsYXNzIiwib24iLCJpcyIsImZhZGVJbiIsInJlbW92ZUNsYXNzIiwicmVxdWlyZSIsImN1cnJlbnRfZnMiLCJuZXh0X2ZzIiwicHJldmlvdXNfZnMiLCJvcGFjaXR5IiwiY3VycmVudCIsInN0ZXBzIiwibGVuZ3RoIiwic2V0UHJvZ3Jlc3NCYXIiLCJjbGljayIsInBhcmVudCIsIm5leHQiLCJlcSIsImluZGV4Iiwic2hvdyIsImFuaW1hdGUiLCJzdGVwIiwibm93IiwiY3NzIiwiZHVyYXRpb24iLCJwcmV2IiwiY3VyU3RlcCIsInBlcmNlbnQiLCJwYXJzZUZsb2F0IiwidG9GaXhlZCJdLCJtYXBwaW5ncyI6Ijs7Ozs7Ozs7Ozs7Ozs7Ozs7QUFBQTs7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFFQTtDQUVBO0FBQ0E7O0FBQ0E7QUFDQTtBQUNBO0FBRUE7QUFDQTtBQUNBQSxPQUFPLENBQUNDLEdBQVIsQ0FBWSxnREFBWjtBQUNBQyxnRUFBQTtBQUVBQyw2Q0FBQyxDQUFDQyxRQUFELENBQUQsQ0FBWUMsS0FBWixDQUFrQixZQUFVO0FBQ3hCRiwrQ0FBQyxDQUFDLGlCQUFELENBQUQsQ0FBcUJHLE9BQXJCO0FBQ0FILCtDQUFDLENBQUMsYUFBRCxDQUFELENBQWlCSSxRQUFqQixDQUEwQixnQkFBMUI7QUFDSCxDQUhEO0FBTUFKLDZDQUFDLENBQUMsc0NBQUQsQ0FBRCxDQUEwQ0ssRUFBMUMsQ0FBNkMsUUFBN0MsRUFBdUQsWUFBVztBQUM5RCxNQUFHTCw2Q0FBQyxDQUFDLGlCQUFELENBQUQsQ0FBcUJNLEVBQXJCLENBQXdCLFVBQXhCLENBQUgsRUFBd0M7QUFDcENOLGlEQUFDLENBQUMsb0JBQUQsQ0FBRCxDQUF3QkcsT0FBeEI7QUFDQUgsaURBQUMsQ0FBQyxpQkFBRCxDQUFELENBQXFCTyxNQUFyQjtBQUVBUCxpREFBQyxDQUFDLGNBQUQsQ0FBRCxDQUFrQkksUUFBbEIsQ0FBMkIsV0FBM0I7QUFDQUosaURBQUMsQ0FBQyxnQkFBRCxDQUFELENBQW9CSSxRQUFwQixDQUE2QixnQkFBN0I7QUFDQUosaURBQUMsQ0FBQyxhQUFELENBQUQsQ0FBaUJRLFdBQWpCLENBQTZCLGdCQUE3QjtBQUVILEdBUkQsTUFTSyxJQUFHUiw2Q0FBQyxDQUFDLGVBQUQsQ0FBRCxDQUFtQk0sRUFBbkIsQ0FBc0IsVUFBdEIsQ0FBSCxFQUFzQztBQUN2Q04saURBQUMsQ0FBQyxvQkFBRCxDQUFELENBQXdCTyxNQUF4QjtBQUNBUCxpREFBQyxDQUFDLGlCQUFELENBQUQsQ0FBcUJHLE9BQXJCO0FBRUFILGlEQUFDLENBQUMsY0FBRCxDQUFELENBQWtCUSxXQUFsQixDQUE4QixXQUE5QjtBQUVBUixpREFBQyxDQUFDLGFBQUQsQ0FBRCxDQUFpQkksUUFBakIsQ0FBMEIsZ0JBQTFCO0FBQ0FKLGlEQUFDLENBQUMsZ0JBQUQsQ0FBRCxDQUFvQlEsV0FBcEIsQ0FBZ0MsZ0JBQWhDO0FBQ0g7QUFDSixDQW5CRCxFOzs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7QUMzQkFDLG1CQUFPLENBQUMseUZBQUQsQ0FBUDs7QUFDQTtBQUNBVCw2Q0FBQyxDQUFDQyxRQUFELENBQUQsQ0FBWUMsS0FBWixDQUFrQixZQUFVO0FBRXhCLE1BQUlRLFVBQUosRUFBZ0JDLE9BQWhCLEVBQXlCQyxXQUF6QixDQUZ3QixDQUVjOztBQUN0QyxNQUFJQyxPQUFKO0FBQ0EsTUFBSUMsT0FBTyxHQUFHLENBQWQ7QUFDQSxNQUFJQyxLQUFLLEdBQUdmLDZDQUFDLENBQUMsVUFBRCxDQUFELENBQWNnQixNQUExQjtBQUVBQyxnQkFBYyxDQUFDSCxPQUFELENBQWQ7QUFFQWQsK0NBQUMsQ0FBQyxPQUFELENBQUQsQ0FBV2tCLEtBQVgsQ0FBaUIsWUFBVTtBQUV2QlIsY0FBVSxHQUFHViw2Q0FBQyxDQUFDLElBQUQsQ0FBRCxDQUFRbUIsTUFBUixFQUFiO0FBQ0FSLFdBQU8sR0FBR1gsNkNBQUMsQ0FBQyxJQUFELENBQUQsQ0FBUW1CLE1BQVIsR0FBaUJDLElBQWpCLEVBQVYsQ0FIdUIsQ0FLL0I7O0FBQ1FwQixpREFBQyxDQUFDLGlCQUFELENBQUQsQ0FBcUJxQixFQUFyQixDQUF3QnJCLDZDQUFDLENBQUMsVUFBRCxDQUFELENBQWNzQixLQUFkLENBQW9CWCxPQUFwQixDQUF4QixFQUFzRFAsUUFBdEQsQ0FBK0QsUUFBL0QsRUFOdUIsQ0FRL0I7O0FBQ1FPLFdBQU8sQ0FBQ1ksSUFBUixHQVR1QixDQVUvQjs7QUFDUWIsY0FBVSxDQUFDYyxPQUFYLENBQW1CO0FBQUNYLGFBQU8sRUFBRTtBQUFWLEtBQW5CLEVBQWlDO0FBQzdCWSxVQUFJLEVBQUUsY0FBU0MsR0FBVCxFQUFjO0FBQ2hDO0FBQ2dCYixlQUFPLEdBQUcsSUFBSWEsR0FBZDtBQUVBaEIsa0JBQVUsQ0FBQ2lCLEdBQVgsQ0FBZTtBQUNYLHFCQUFXLE1BREE7QUFFWCxzQkFBWTtBQUZELFNBQWY7QUFJQWhCLGVBQU8sQ0FBQ2dCLEdBQVIsQ0FBWTtBQUFDLHFCQUFXZDtBQUFaLFNBQVo7QUFDSCxPQVY0QjtBQVc3QmUsY0FBUSxFQUFFO0FBWG1CLEtBQWpDO0FBYUFYLGtCQUFjLENBQUMsRUFBRUgsT0FBSCxDQUFkO0FBQ0gsR0F6QkQ7QUEyQkFkLCtDQUFDLENBQUMsV0FBRCxDQUFELENBQWVrQixLQUFmLENBQXFCLFlBQVU7QUFFM0JSLGNBQVUsR0FBR1YsNkNBQUMsQ0FBQyxJQUFELENBQUQsQ0FBUW1CLE1BQVIsRUFBYjtBQUNBUCxlQUFXLEdBQUdaLDZDQUFDLENBQUMsSUFBRCxDQUFELENBQVFtQixNQUFSLEdBQWlCVSxJQUFqQixFQUFkLENBSDJCLENBS25DOztBQUNRN0IsaURBQUMsQ0FBQyxpQkFBRCxDQUFELENBQXFCcUIsRUFBckIsQ0FBd0JyQiw2Q0FBQyxDQUFDLFVBQUQsQ0FBRCxDQUFjc0IsS0FBZCxDQUFvQlosVUFBcEIsQ0FBeEIsRUFBeURGLFdBQXpELENBQXFFLFFBQXJFLEVBTjJCLENBUW5DOztBQUNRSSxlQUFXLENBQUNXLElBQVosR0FUMkIsQ0FXbkM7O0FBQ1FiLGNBQVUsQ0FBQ2MsT0FBWCxDQUFtQjtBQUFDWCxhQUFPLEVBQUU7QUFBVixLQUFuQixFQUFpQztBQUM3QlksVUFBSSxFQUFFLGNBQVNDLEdBQVQsRUFBYztBQUNoQztBQUNnQmIsZUFBTyxHQUFHLElBQUlhLEdBQWQ7QUFFQWhCLGtCQUFVLENBQUNpQixHQUFYLENBQWU7QUFDWCxxQkFBVyxNQURBO0FBRVgsc0JBQVk7QUFGRCxTQUFmO0FBSUFmLG1CQUFXLENBQUNlLEdBQVosQ0FBZ0I7QUFBQyxxQkFBV2Q7QUFBWixTQUFoQjtBQUNILE9BVjRCO0FBVzdCZSxjQUFRLEVBQUU7QUFYbUIsS0FBakM7QUFhQVgsa0JBQWMsQ0FBQyxFQUFFSCxPQUFILENBQWQ7QUFDSCxHQTFCRDs7QUE0QkEsV0FBU0csY0FBVCxDQUF3QmEsT0FBeEIsRUFBZ0M7QUFDNUIsUUFBSUMsT0FBTyxHQUFHQyxVQUFVLENBQUMsTUFBTWpCLEtBQVAsQ0FBVixHQUEwQmUsT0FBeEM7QUFDQUMsV0FBTyxHQUFHQSxPQUFPLENBQUNFLE9BQVIsRUFBVjtBQUNBakMsaURBQUMsQ0FBQyxlQUFELENBQUQsQ0FDSzJCLEdBREwsQ0FDUyxPQURULEVBQ2lCSSxPQUFPLEdBQUMsR0FEekI7QUFFSDs7QUFFRC9CLCtDQUFDLENBQUMsU0FBRCxDQUFELENBQWFrQixLQUFiLENBQW1CLFlBQVU7QUFDekIsV0FBTyxLQUFQO0FBQ0gsR0FGRDtBQUlILENBM0VELEU7Ozs7Ozs7Ozs7OztBQ0ZBIiwiZmlsZSI6ImFwcC5qcyIsInNvdXJjZXNDb250ZW50IjpbIi8vIGFzc2V0cy9hcHAuanNcclxuLypcclxuICogV2VsY29tZSB0byB5b3VyIGFwcCdzIG1haW4gSmF2YVNjcmlwdCBmaWxlIVxyXG4gKlxyXG4gKiBXZSByZWNvbW1lbmQgaW5jbHVkaW5nIHRoZSBidWlsdCB2ZXJzaW9uIG9mIHRoaXMgSmF2YVNjcmlwdCBmaWxlXHJcbiAqIChhbmQgaXRzIENTUyBmaWxlKSBpbiB5b3VyIGJhc2UgbGF5b3V0IChiYXNlLmh0bWwudHdpZykuXHJcbiAqL1xyXG5cclxuLy8gYW55IENTUyB5b3UgaW1wb3J0IHdpbGwgb3V0cHV0IGludG8gYSBzaW5nbGUgY3NzIGZpbGUgKGFwcC5jc3MgaW4gdGhpcyBjYXNlKVxyXG5pbXBvcnQgJy4vc3R5bGVzL2FwcC5zY3NzJztcclxuLy8gTmVlZCBqUXVlcnk/IEluc3RhbGwgaXQgd2l0aCBcInlhcm4gYWRkIGpxdWVyeVwiLCB0aGVuIHVuY29tbWVudCB0byBpbXBvcnQgaXQuXHJcbi8vIGltcG9ydCAkIGZyb20gJ2pxdWVyeSc7XHJcbmltcG9ydCAncG9wcGVyLmpzJztcclxuaW1wb3J0ICdib290c3RyYXAnO1xyXG5pbXBvcnQgJCBmcm9tICdqcXVlcnknO1xyXG5cclxuaW1wb3J0ICcuL2phdmFzY3JpcHQvY3VzdG9tLmpzJztcclxuaW1wb3J0IGJzQ3VzdG9tRmlsZUlucHV0IGZyb20gJ2JzLWN1c3RvbS1maWxlLWlucHV0JztcclxuY29uc29sZS5sb2coJ0hlbGxvIFdlYnBhY2sgRW5jb3JlISBFZGl0IG1lIGluIGFzc2V0cy9hcHAuanMnKTtcclxuYnNDdXN0b21GaWxlSW5wdXQuaW5pdCgpO1xyXG5cclxuJChkb2N1bWVudCkucmVhZHkoZnVuY3Rpb24oKXtcclxuICAgICQoJy5sb2dpbi1pbmZvLWJveCcpLmZhZGVPdXQoKTtcclxuICAgICQoJy5sb2dpbi1zaG93JykuYWRkQ2xhc3MoJ3Nob3ctbG9nLXBhbmVsJyk7XHJcbn0pO1xyXG5cclxuXHJcbiQoJy5sb2dpbi1yZWctcGFuZWwgaW5wdXRbdHlwZT1cInJhZGlvXCJdJykub24oJ2NoYW5nZScsIGZ1bmN0aW9uKCkge1xyXG4gICAgaWYoJCgnI2xvZy1sb2dpbi1zaG93JykuaXMoJzpjaGVja2VkJykpIHtcclxuICAgICAgICAkKCcucmVnaXN0ZXItaW5mby1ib3gnKS5mYWRlT3V0KCk7XHJcbiAgICAgICAgJCgnLmxvZ2luLWluZm8tYm94JykuZmFkZUluKCk7XHJcblxyXG4gICAgICAgICQoJy53aGl0ZS1wYW5lbCcpLmFkZENsYXNzKCdyaWdodC1sb2cnKTtcclxuICAgICAgICAkKCcucmVnaXN0ZXItc2hvdycpLmFkZENsYXNzKCdzaG93LWxvZy1wYW5lbCcpO1xyXG4gICAgICAgICQoJy5sb2dpbi1zaG93JykucmVtb3ZlQ2xhc3MoJ3Nob3ctbG9nLXBhbmVsJyk7XHJcblxyXG4gICAgfVxyXG4gICAgZWxzZSBpZigkKCcjbG9nLXJlZy1zaG93JykuaXMoJzpjaGVja2VkJykpIHtcclxuICAgICAgICAkKCcucmVnaXN0ZXItaW5mby1ib3gnKS5mYWRlSW4oKTtcclxuICAgICAgICAkKCcubG9naW4taW5mby1ib3gnKS5mYWRlT3V0KCk7XHJcblxyXG4gICAgICAgICQoJy53aGl0ZS1wYW5lbCcpLnJlbW92ZUNsYXNzKCdyaWdodC1sb2cnKTtcclxuXHJcbiAgICAgICAgJCgnLmxvZ2luLXNob3cnKS5hZGRDbGFzcygnc2hvdy1sb2ctcGFuZWwnKTtcclxuICAgICAgICAkKCcucmVnaXN0ZXItc2hvdycpLnJlbW92ZUNsYXNzKCdzaG93LWxvZy1wYW5lbCcpO1xyXG4gICAgfVxyXG59KTtcclxuXHJcblxyXG4iLCJyZXF1aXJlKFwiY29yZS1qcy9tb2R1bGVzL2VzLmFycmF5LmZpbmQuanNcIik7XHJcbmltcG9ydCAkIGZyb20gJ2pxdWVyeSc7XHJcbiQoZG9jdW1lbnQpLnJlYWR5KGZ1bmN0aW9uKCl7XHJcblxyXG4gICAgdmFyIGN1cnJlbnRfZnMsIG5leHRfZnMsIHByZXZpb3VzX2ZzOyAvL2ZpZWxkc2V0c1xyXG4gICAgdmFyIG9wYWNpdHk7XHJcbiAgICB2YXIgY3VycmVudCA9IDE7XHJcbiAgICB2YXIgc3RlcHMgPSAkKFwiZmllbGRzZXRcIikubGVuZ3RoO1xyXG5cclxuICAgIHNldFByb2dyZXNzQmFyKGN1cnJlbnQpO1xyXG5cclxuICAgICQoXCIubmV4dFwiKS5jbGljayhmdW5jdGlvbigpe1xyXG5cclxuICAgICAgICBjdXJyZW50X2ZzID0gJCh0aGlzKS5wYXJlbnQoKTtcclxuICAgICAgICBuZXh0X2ZzID0gJCh0aGlzKS5wYXJlbnQoKS5uZXh0KCk7XHJcblxyXG4vL0FkZCBDbGFzcyBBY3RpdmVcclxuICAgICAgICAkKFwiI3Byb2dyZXNzYmFyIGxpXCIpLmVxKCQoXCJmaWVsZHNldFwiKS5pbmRleChuZXh0X2ZzKSkuYWRkQ2xhc3MoXCJhY3RpdmVcIik7XHJcblxyXG4vL3Nob3cgdGhlIG5leHQgZmllbGRzZXRcclxuICAgICAgICBuZXh0X2ZzLnNob3coKTtcclxuLy9oaWRlIHRoZSBjdXJyZW50IGZpZWxkc2V0IHdpdGggc3R5bGVcclxuICAgICAgICBjdXJyZW50X2ZzLmFuaW1hdGUoe29wYWNpdHk6IDB9LCB7XHJcbiAgICAgICAgICAgIHN0ZXA6IGZ1bmN0aW9uKG5vdykge1xyXG4vLyBmb3IgbWFraW5nIGZpZWxzZXQgYXBwZWFyIGFuaW1hdGlvblxyXG4gICAgICAgICAgICAgICAgb3BhY2l0eSA9IDEgLSBub3c7XHJcblxyXG4gICAgICAgICAgICAgICAgY3VycmVudF9mcy5jc3Moe1xyXG4gICAgICAgICAgICAgICAgICAgICdkaXNwbGF5JzogJ25vbmUnLFxyXG4gICAgICAgICAgICAgICAgICAgICdwb3NpdGlvbic6ICdyZWxhdGl2ZSdcclxuICAgICAgICAgICAgICAgIH0pO1xyXG4gICAgICAgICAgICAgICAgbmV4dF9mcy5jc3MoeydvcGFjaXR5Jzogb3BhY2l0eX0pO1xyXG4gICAgICAgICAgICB9LFxyXG4gICAgICAgICAgICBkdXJhdGlvbjogNTAwXHJcbiAgICAgICAgfSk7XHJcbiAgICAgICAgc2V0UHJvZ3Jlc3NCYXIoKytjdXJyZW50KTtcclxuICAgIH0pO1xyXG5cclxuICAgICQoXCIucHJldmlvdXNcIikuY2xpY2soZnVuY3Rpb24oKXtcclxuXHJcbiAgICAgICAgY3VycmVudF9mcyA9ICQodGhpcykucGFyZW50KCk7XHJcbiAgICAgICAgcHJldmlvdXNfZnMgPSAkKHRoaXMpLnBhcmVudCgpLnByZXYoKTtcclxuXHJcbi8vUmVtb3ZlIGNsYXNzIGFjdGl2ZVxyXG4gICAgICAgICQoXCIjcHJvZ3Jlc3NiYXIgbGlcIikuZXEoJChcImZpZWxkc2V0XCIpLmluZGV4KGN1cnJlbnRfZnMpKS5yZW1vdmVDbGFzcyhcImFjdGl2ZVwiKTtcclxuXHJcbi8vc2hvdyB0aGUgcHJldmlvdXMgZmllbGRzZXRcclxuICAgICAgICBwcmV2aW91c19mcy5zaG93KCk7XHJcblxyXG4vL2hpZGUgdGhlIGN1cnJlbnQgZmllbGRzZXQgd2l0aCBzdHlsZVxyXG4gICAgICAgIGN1cnJlbnRfZnMuYW5pbWF0ZSh7b3BhY2l0eTogMH0sIHtcclxuICAgICAgICAgICAgc3RlcDogZnVuY3Rpb24obm93KSB7XHJcbi8vIGZvciBtYWtpbmcgZmllbHNldCBhcHBlYXIgYW5pbWF0aW9uXHJcbiAgICAgICAgICAgICAgICBvcGFjaXR5ID0gMSAtIG5vdztcclxuXHJcbiAgICAgICAgICAgICAgICBjdXJyZW50X2ZzLmNzcyh7XHJcbiAgICAgICAgICAgICAgICAgICAgJ2Rpc3BsYXknOiAnbm9uZScsXHJcbiAgICAgICAgICAgICAgICAgICAgJ3Bvc2l0aW9uJzogJ3JlbGF0aXZlJ1xyXG4gICAgICAgICAgICAgICAgfSk7XHJcbiAgICAgICAgICAgICAgICBwcmV2aW91c19mcy5jc3MoeydvcGFjaXR5Jzogb3BhY2l0eX0pO1xyXG4gICAgICAgICAgICB9LFxyXG4gICAgICAgICAgICBkdXJhdGlvbjogNTAwXHJcbiAgICAgICAgfSk7XHJcbiAgICAgICAgc2V0UHJvZ3Jlc3NCYXIoLS1jdXJyZW50KTtcclxuICAgIH0pO1xyXG5cclxuICAgIGZ1bmN0aW9uIHNldFByb2dyZXNzQmFyKGN1clN0ZXApe1xyXG4gICAgICAgIHZhciBwZXJjZW50ID0gcGFyc2VGbG9hdCgxMDAgLyBzdGVwcykgKiBjdXJTdGVwO1xyXG4gICAgICAgIHBlcmNlbnQgPSBwZXJjZW50LnRvRml4ZWQoKTtcclxuICAgICAgICAkKFwiLnByb2dyZXNzLWJhclwiKVxyXG4gICAgICAgICAgICAuY3NzKFwid2lkdGhcIixwZXJjZW50K1wiJVwiKVxyXG4gICAgfVxyXG5cclxuICAgICQoXCIuc3VibWl0XCIpLmNsaWNrKGZ1bmN0aW9uKCl7XHJcbiAgICAgICAgcmV0dXJuIGZhbHNlO1xyXG4gICAgfSlcclxuXHJcbn0pOyIsIi8vIGV4dHJhY3RlZCBieSBtaW5pLWNzcy1leHRyYWN0LXBsdWdpblxuZXhwb3J0IHt9OyJdLCJzb3VyY2VSb290IjoiIn0=