/*
 * ATTENTION: An "eval-source-map" devtool has been used.
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file with attached SourceMaps in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/js/app.js":
/*!***********************!*\
  !*** ./src/js/app.js ***!
  \***********************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _scss_app_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../scss/app.scss */ \"./src/scss/app.scss\");\n\nconsole.log('app.js');\n/* Your JS Code goes here *///# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiLi9zcmMvanMvYXBwLmpzIiwibWFwcGluZ3MiOiI7O0FBQTBCO0FBRTFCQSxPQUFPLENBQUNDLEdBQUcsQ0FBQyxRQUFRLENBQUM7QUFDckIiLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly9Ad2VhcmVhdGhsb24vZnJvbnRlbmQtd2VicGFjay1ib2lsZXJwbGF0ZS8uL3NyYy9qcy9hcHAuanM/OTBlOSJdLCJzb3VyY2VzQ29udGVudCI6WyJpbXBvcnQgJy4uL3Njc3MvYXBwLnNjc3MnO1xuXG5jb25zb2xlLmxvZygnYXBwLmpzJyk7XG4vKiBZb3VyIEpTIENvZGUgZ29lcyBoZXJlICovXG4iXSwibmFtZXMiOlsiY29uc29sZSIsImxvZyJdLCJzb3VyY2VSb290IjoiIn0=\n//# sourceURL=webpack-internal:///./src/js/app.js\n");

/***/ }),

/***/ "./src/scss/app.scss":
/*!***************************!*\
  !*** ./src/scss/app.scss ***!
  \***************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n// extracted by mini-css-extract-plugin\n//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiLi9zcmMvc2Nzcy9hcHAuc2NzcyIsIm1hcHBpbmdzIjoiO0FBQUEiLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly9Ad2VhcmVhdGhsb24vZnJvbnRlbmQtd2VicGFjay1ib2lsZXJwbGF0ZS8uL3NyYy9zY3NzL2FwcC5zY3NzPzJlYTgiXSwic291cmNlc0NvbnRlbnQiOlsiLy8gZXh0cmFjdGVkIGJ5IG1pbmktY3NzLWV4dHJhY3QtcGx1Z2luXG5leHBvcnQge307Il0sIm5hbWVzIjpbXSwic291cmNlUm9vdCI6IiJ9\n//# sourceURL=webpack-internal:///./src/scss/app.scss\n");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval-source-map devtool is used.
/******/ 	var __webpack_exports__ = __webpack_require__("./src/js/app.js");
/******/ 	
/******/ })()
;

// Скрипт для возможности запиннеть лигу пользователем
(function(){
  const STORAGE_KEY = 'sgw_user_pins_v1';

  // ===== Storage helpers =====
  function readPins(){
    try {
      const raw = sessionStorage.getItem(STORAGE_KEY);
      const arr = raw ? JSON.parse(raw) : [];
      return Array.isArray(arr) ? arr : [];
    } catch(e){ return []; }
  }
  function savePins(arr){
    try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(arr)); } catch(e){}
  }
  function keyOf(meta){
    // Стабильный ключ — предпочитаем id, иначе slug
    return meta && (String(meta.id||'').trim() || String(meta.slug||'').trim());
  }
  function isSame(a,b){
    return keyOf(a) && keyOf(a) === keyOf(b);
  }

  // ===== CRUD pins =====
  function isPinned(meta){
    const pins = readPins();
    return pins.some(p => isSame(p, meta));
  }
  function addPin(meta){
    if (!keyOf(meta)) return;
    const pins = readPins();
    if (!pins.some(p => isSame(p, meta))){
      pins.push({
        id:   meta.id || null,
        slug: meta.slug || null,
        title: meta.title || '',
        url:  meta.url || '#'
      });
      savePins(pins);
    }
  }
  function removePin(meta){
    if (!keyOf(meta)) return;
    const pins = readPins().filter(p => !isSame(p, meta));
    savePins(pins);
  }
  function togglePin(meta){
    if (isPinned(meta)) removePin(meta);
    else addPin(meta);
  }

  // ===== UI: Sidebar render (append user pins after server ones) =====
  function renderPinnedList(){
    const list = document.getElementById('sgw-pinned-list');
    if (!list) return;

    // Удаляем только ранее добавленные JS-элементы
    list.querySelectorAll('li[data-user-pin="1"]').forEach(li => li.remove());

    const pins = readPins();
    if (!pins.length) return;

    const frag = document.createDocumentFragment();
    pins.forEach(p => {
      const li = document.createElement('li');
      li.setAttribute('data-user-pin', '1');
      const a = document.createElement('a');
      a.href = p.url || '#';
      a.textContent = p.title || (p.slug || p.id || 'League');
      li.appendChild(a);
      frag.appendChild(li);
    });
    list.appendChild(frag);
  }

  // ===== UI: Button state sync =====
  function metaFromBtn(btn){
    return {
      id:   (btn.getAttribute('data-pin-id') || '').trim(),
      slug: (btn.getAttribute('data-pin-slug') || '').trim(),
      title:(btn.getAttribute('data-pin-title') || '').trim(),
      url:  (btn.getAttribute('data-pin-url') || '').trim(),
    };
  }
  function applyBtnState(btn){
    const meta = metaFromBtn(btn);
    const pinned = isPinned(meta);
    btn.classList.toggle('is-pinned', pinned);
    btn.title = pinned ? 'Unpin league' : 'Pin this league';
    btn.setAttribute('aria-label', btn.title);
  }
  function syncAllButtons(){
    document.querySelectorAll('.sgw-pin-btn').forEach(applyBtnState);
  }

  // ===== Events =====
  document.addEventListener('click', function(e){
    const btn = e.target.closest('.sgw-pin-btn');
    if (!btn) return;

    const meta = metaFromBtn(btn);
    if (!keyOf(meta)) return;

    togglePin(meta);
    applyBtnState(btn);
    renderPinnedList();
  });

  // ===== Init on load =====
  document.addEventListener('DOMContentLoaded', function(){
    renderPinnedList();
    syncAllButtons();
  });

  // Если контент подгружается динамически (SPA / ajax), можно вызвать публичный ресинк:
  window.SGW_Pins = {
    refresh: function(){
      renderPinnedList();
      syncAllButtons();
    }
  };
})();