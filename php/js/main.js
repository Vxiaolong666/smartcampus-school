window.addEventListener('load', () => {
    const preloader = document.getElementById('preloader');
    preloader.classList.remove('active');
});

function initApp() {
    //custom cursor -----------------------------------------------------------------------------------------------
    const customCursor = document.querySelector('.custom-cursor');
    const hoverTargets = document.querySelectorAll('.swiper, .carousel-wrapper, .service_slide, .slick-slider');

    document.addEventListener('mousemove', (e) => {
        customCursor.style.transform = `translate(${e.clientX}px, ${e.clientY}px)`;
    });

    hoverTargets.forEach((el) => {
        el.addEventListener("mouseenter", () => {
          customCursor.style.opacity = "1";
        });
        el.addEventListener("mouseleave", () => {
          customCursor.style.opacity = "0";
        });
    });
    //-------------------------------------------------------------------------------------------------------------

    //active nav in scroll ----------------------------------------------------------------------------------------
    function initScrollSpy({ linkSelector, activeClass = "active", scrollOffset = 0, activeOffset = 0 }) {
        const navLinks = document.querySelectorAll(linkSelector);
    
        const sectionMap = {};
        navLinks.forEach(link => {
            const id = link.getAttribute("href")?.slice(1);
            if (id) {
                if (!sectionMap[id]) sectionMap[id] = [];
                sectionMap[id].push(link);
    
                // Add smooth scroll with offset
                link.addEventListener("click", function (e) {
                    const target = document.getElementById(id);
                    if (target) {
                        e.preventDefault();
                        const scrollTargetY = target.offsetTop - scrollOffset;
                        window.scrollTo({
                            top: scrollTargetY,
                            behavior: "smooth"
                        });
                    }
                });
            }
        });
    
        const sectionIds = Object.keys(sectionMap);
        const sections = sectionIds
            .map(id => document.getElementById(id))
            .filter(Boolean);
    
        function onScroll() {
            let currentSectionId = null;
            const scrollY = window.scrollY;
    
            for (let i = 0; i < sections.length; i++) {
                const sectionTop = sections[i].offsetTop - activeOffset;
                const sectionBottom = sectionTop + sections[i].offsetHeight;
                if (scrollY >= sectionTop && scrollY < sectionBottom) {
                    currentSectionId = sections[i].id;
                    break;
                }
            }
    
            navLinks.forEach(link => link.classList.remove(activeClass));
            if (sectionMap[currentSectionId]) {
                sectionMap[currentSectionId].forEach(link => link.classList.add(activeClass));
            }
        }
    
        window.addEventListener("scroll", onScroll);
        onScroll();
    }

    initScrollSpy({
        linkSelector: '.list-group-item-action[href^="#"]',
        activeClass: 'active',
        scrollOffset: 150,
        activeOffset: 150
    });
    //-------------------------------------------------------------------------------------------------------------

    //style header when scrolled -----------------------------------------------------------------------------------
    const toggleStylHeaderAndScrollUp = () => {
        const shouldScroll = window.scrollY > 50;
        document.body.classList.toggle('scrolled', shouldScroll);
    };

    window.addEventListener('scroll', function () {
        toggleStylHeaderAndScrollUp()
    });

    toggleStylHeaderAndScrollUp();
    //-------------------------------------------------------------------------------------------------------------

    //accordion benefits ------------------------------------------------------------------------------------------
    const accordionButtons = document.querySelectorAll('#benefitAccordion .accordion-button');
    const allImages = document.querySelectorAll('.image-box img');

    accordionButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetImgId = button.getAttribute('data-img');
            allImages.forEach(img => {
                img.classList.remove('active');
            });
            const activeImg = document.getElementById(targetImgId);
            if (activeImg) {
                activeImg.classList.add('active');
            }
        });
    });
    //-------------------------------------------------------------------------------------------------------------

    //aos animate -------------------------------------------------------------------------------------------------
    // 注意：easing 必须是 AOS 支持的值（此前误写成 easing-in-sine，非合法值会导致动画失效）；
    // once:true 让元素显示后保持可见，避免滚动过程中偶发回到隐藏态造成空白。
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 600,
            easing: 'ease-in-sine',
            delay: 0,
            offset: 0,
            once: true
        });
    } else {
        // AOS 脚本未加载（缓存/网络异常）：直接显示所有动画元素，避免整页内容停在 opacity:0
        revealAosElements();
    }
    //-------------------------------------------------------------------------------------------------------------

    //slider testimonial with swiper js ---------------------------------------------------------------------------
    const testimonialEl = document.querySelector('#sliderTestimonials');
    if (testimonialEl) {
        const swiperSliderTestimonial = new Swiper("#sliderTestimonials", {
            slidesPerView: 2,
            spaceBetween: 24,
            loop: true,
            autoplay: {
                delay: 3000,
                disableOnInteraction: false,
            },
            breakpoints: {
                0 : {
                    slidesPerView: 1
                },

                1199: {
                    slidesPerView: 2
                }
            }
        });
    }
    //slider testimonial area (download page, 1 card + arrows) ------------------------------------------------------
    const testimonialAreaEl = document.querySelector('#sliderTestimonialsArea');
    if (testimonialAreaEl) {
        const swiperSliderTestimonialsArea = new Swiper("#sliderTestimonialsArea", {
            slidesPerView: 1,
            spaceBetween: 24,
            loop: true,
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
            },
            navigation: {
                nextEl: '.testimonial-nav-next',
                prevEl: '.testimonial-nav-prev',
            },
        });
    }
    //-------------------------------------------------------------------------------------------------------------

    //slider partner with swiper js -------------------------------------------------------------------------------
    const partnersEl = document.querySelector('#sliderPartners');
    if (partnersEl) {
        const swiperSliderPartners = new Swiper('#sliderPartners', {
            slidesPerView: 5,
            loop: true,
            autoplay: {
                delay: 2500,
                disableOnInteraction: false,
            },
            breakpoints: {
                0 : {
                    slidesPerView: 2
                },

                480: {
                    slidesPerView: 3
                },

                767: {
                    slidesPerView: 4,
                },

                1199: {
                    slidesPerView: 6
                }
            }
        });
    }
    //-------------------------------------------------------------------------------------------------------------

    //fancybox video-----------------------------------------------------------------------------------------------
    Fancybox.bind("[data-fancybox]", {
        // Your custom options
    });
    //-------------------------------------------------------------------------------------------------------------
    // 下载页：点击跳转到下载页
    const checkNowBtn = document.getElementById('checkNowBtn');
    if (checkNowBtn) {
        checkNowBtn.addEventListener('click', () => {
            window.location.href = 'download.html';
        });
    }
    //-------------------------------------------------------------------------------------------------------------
}

// 初始化只执行一次（下面有多个触发源，可能先后到达）
let __appInited = false;
function initAppOnce() {
    if (__appInited) return;
    __appInited = true;
    initApp();
}

// 若页面使用了公共组件（header/footer），组件注入完成后再初始化。
// 关键：不能只依赖 componentsLoaded 事件——它由 component-loader 用 setTimeout 派发，
// 若此刻 main.js 仍在下载，事件会先于监听器注册而派发丢失，导致整页不初始化
// （AOS 元素停在 opacity:0 → 主内容空白）。因此改用「同步标记 + 事件 + DOMContentLoaded/load 兜底」多重保障。
if (document.querySelector('[data-component]')) {
    if (window.__componentsLoaded) {
        initAppOnce();
    } else {
        document.addEventListener('componentsLoaded', initAppOnce);
    }
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAppOnce);
} else {
    initAppOnce();
}
window.addEventListener('load', initAppOnce);

// AOS 兜底：让所有 [data-aos] 元素直接显示（AOS 未生效时使用，避免整页空白）
function revealAosElements() {
    document.querySelectorAll('[data-aos]').forEach(function (el) {
        el.classList.add('aos-animate');
    });
}

// AOS 兜底：初始化后仍在视口内却未显示的元素，直接显示（修时序/网络导致的偶发空白）
window.addEventListener('load', function () {
    document.querySelectorAll('[data-aos]:not(.aos-animate)').forEach(function (el) {
        var rect = el.getBoundingClientRect();
        if (rect.top < window.innerHeight && rect.bottom > 0) {
            el.classList.add('aos-animate');
        }
    });
});

// Back to top (matching reference template)
function topFunction() {
    window.scrollTo({ top: 0, behavior: "smooth" });
}

// Development tabs (about page, clone of scraped_site custom.js)
if (document.querySelector('.development-tabs')) {
    document.querySelectorAll('.development-tabs').forEach((group) => {
        const items = group.querySelectorAll('.tabs-heading li');
        const contents = group.querySelectorAll('.tab-content');
        items.forEach((li) => {
            li.addEventListener('click', () => {
                items.forEach((i) => i.classList.remove('current'));
                contents.forEach((c) => c.classList.remove('current'));
                li.classList.add('current');
                const target = document.getElementById(li.dataset.tab);
                if (target) target.classList.add('current');
            });
        });
    });
}

// Progress bars (about page, clone of scraped_site custom.js)
if (document.querySelector('.seomek-progress')) {
    const runProgress = () => {
        document.querySelectorAll('.seomek-progress').forEach((p) => {
            const bar = p.querySelector('.progress-bar');
            if (bar && !bar.dataset.done && p.getBoundingClientRect().top < window.innerHeight - 100) {
                bar.style.width = bar.getAttribute('data-percent');
                bar.dataset.done = '1';
            }
        });
    };
    window.addEventListener('scroll', runProgress);
    runProgress();
}

// 首页服务区轮播（Swiper）
const serviceSliderEl = document.querySelector('#serviceSlider');
if (serviceSliderEl) {
    new Swiper('#serviceSlider', {
        slidesPerView: 1,
        spaceBetween: 24,
        loop: true,
        grabCursor: true,
        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
        },
        speed: 800,
        pagination: {
            el: '#serviceSlider .swiper-pagination',
            clickable: true,
        },
        breakpoints: {
            576: { slidesPerView: 2 },
            992: { slidesPerView: 2 },
            1200: { slidesPerView: 3 },
        },
    });
}