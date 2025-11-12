document.addEventListener("DOMContentLoaded", () => {
  const trashEmojis = ["📦", "🗑️", "📄", "🥤", "🍌", "📦", "🧻"]

  const binCards = document.querySelectorAll(".bin-card")

  binCards.forEach((card) => {
    const binVisual = card.querySelector(".bin-visual")
    const binFill = card.querySelector(".bin-fill")
    const percentageElement = card.querySelector(".bin-percentage")

    // Get the fill percentage from the height
    const fillHeight = Number.parseInt(window.getComputedStyle(binFill).height, 10)
    const visualHeight = Number.parseInt(window.getComputedStyle(binVisual).height, 10)
    const fillPercent = (fillHeight / visualHeight) * 100

    // Determine animation speed based on fill level
    let animationDelay = 2.5 // Empty bin - slower
    let itemCount = 2

    if (fillPercent >= 50 && fillPercent < 100) {
      animationDelay = 1.8 // Half-full - medium speed
      itemCount = 3
    } else if (fillPercent >= 100) {
      animationDelay = 1.4 // Full - faster
      itemCount = 4
    }

    // Create continuous trash falling animation
    function createFallingTrash() {
      for (let i = 0; i < itemCount; i++) {
        setTimeout(
          () => {
            const trash = document.createElement("div")
            trash.classList.add("trash-item")
            trash.textContent = trashEmojis[Math.floor(Math.random() * trashEmojis.length)]
            trash.style.left = Math.random() * 80 + 10 + "%"
            trash.style.animationDuration = animationDelay + "s"
            trash.style.animationDelay = i * (animationDelay / itemCount) + "s"

            binVisual.appendChild(trash)

            // Remove trash item after animation completes
            setTimeout(
              () => {
                trash.remove()
              },
              animationDelay * 1000 + i * (animationDelay / itemCount) * 1000,
            )
          },
          i * (animationDelay / itemCount) * 1000,
        )
      }
    }

    // Start animation when element comes into view
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          createFallingTrash()
          // Repeat animation every cycle
          setInterval(createFallingTrash, animationDelay * 2000)
          observer.unobserve(entry.target)
        }
      })
    })

    observer.observe(binVisual)
  })
})
