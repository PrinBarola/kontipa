let currentSlide = 0
const slides = document.querySelectorAll(".slide")
const totalSlides = slides.length
let autoSlideInterval

function showSlide(n) {
  slides.forEach((slide) => slide.classList.remove("active"))
  document.querySelectorAll(".indicator").forEach((indicator) => indicator.classList.remove("active"))

  if (n >= totalSlides) currentSlide = 0
  if (n < 0) currentSlide = totalSlides - 1

  slides[currentSlide].classList.add("active")
  document.querySelectorAll(".indicator")[currentSlide].classList.add("active")
}

function nextSlide() {
  currentSlide++
  showSlide(currentSlide)
  resetAutoSlide()
}

function previousSlide() {
  currentSlide--
  showSlide(currentSlide)
  resetAutoSlide()
}

function goToSlide(n) {
  currentSlide = n
  showSlide(currentSlide)
  resetAutoSlide()
}

function autoSlide() {
  currentSlide++
  showSlide(currentSlide)
}

function resetAutoSlide() {
  clearInterval(autoSlideInterval)
  autoSlideInterval = setInterval(autoSlide, 8000)
}

// Initialize
showSlide(currentSlide)
autoSlideInterval = setInterval(autoSlide, 8000)
