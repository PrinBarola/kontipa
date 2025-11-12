/* Glassmorphism Design System */
:root {
  /* Glass colors */
  --glass-bg: rgba(20, 25, 35, 0.7);
  --glass-bg-light: rgba(30, 40, 55, 0.6);
  --glass-border: rgba(255, 255, 255, 0.1);
  --glass-border-light: rgba(255, 255, 255, 0.15);

  /* Neon accent colors */
  --neon-cyan: #00d4ff;
  --neon-cyan-glow: rgba(0, 212, 255, 0.3);
  --neon-pink: #ff006e;
  --neon-blue: #0095ff;

  /* Gradients */
  --glass-gradient: linear-gradient(135deg, rgba(100, 200, 255, 0.1) 0%, rgba(255, 0, 110, 0.1) 100%);
}

/* Base glass card styles */
.glass-card {
  background: var(--glass-bg);
  border: 1px solid var(--glass-border);
  border-radius: 20px;
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  padding: 32px;
  transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
  position: relative;
  overflow: hidden;
}

.glass-card::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: var(--glass-gradient);
  opacity: 0;
  transition: opacity 0.4s ease;
  pointer-events: none;
}

.glass-card:hover {
  border-color: var(--glass-border-light);
  background: rgba(30, 40, 55, 0.8);
  transform: translateY(-8px);
  box-shadow: 0 8px 32px rgba(0, 212, 255, 0.1), 0 8px 16px rgba(255, 0, 110, 0.05);
}

.glass-card:hover::before {
  opacity: 1;
}

/* Glassmorphic title styles */
.glass-title {
  font-size: 24px;
  font-weight: 700;
  background: linear-gradient(135deg, #ffffff 0%, var(--neon-cyan) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 16px;
}

.glass-subtitle {
  color: rgba(255, 255, 255, 0.7);
  font-size: 14px;
  line-height: 1.6;
}

/* Glassmorphic icon containers */
.glass-icon-container {
  width: 80px;
  height: 80px;
  border-radius: 16px;
  background: var(--glass-bg-light);
  border: 1px solid var(--glass-border-light);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;
  font-size: 36px;
  color: var(--neon-cyan);
  box-shadow: 0 4px 16px rgba(0, 212, 255, 0.1);
  transition: all 0.3s ease;
}

.glass-card:hover .glass-icon-container {
  background: rgba(0, 212, 255, 0.1);
  box-shadow: 0 8px 24px rgba(0, 212, 255, 0.2);
  transform: scale(1.05);
}

/* Glassmorphic button */
.glass-btn {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 12px 28px;
  border-radius: 50px;
  background: linear-gradient(135deg, var(--neon-blue) 0%, var(--neon-cyan) 100%);
  color: white;
  border: 1px solid var(--neon-cyan);
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s ease;
  text-decoration: none;
  box-shadow: 0 4px 16px rgba(0, 212, 255, 0.3);
  margin-top: 16px;
}

.glass-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 212, 255, 0.5);
  background: linear-gradient(135deg, var(--neon-cyan) 0%, var(--neon-blue) 100%);
}

/* Grid layouts for glass cards */
.glass-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 28px;
  margin-top: 40px;
}

.glass-grid-wide {
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 24px;
}

/* Glassmorphic section background */
.glass-section {
  position: relative;
  background: linear-gradient(135deg, rgba(10, 15, 25, 0.95) 0%, rgba(20, 30, 45, 0.95) 100%);
  padding: 80px 32px;
  overflow: hidden;
}

.glass-section::before {
  content: "";
  position: absolute;
  top: -50%;
  right: -50%;
  width: 100%;
  height: 100%;
  background: radial-gradient(circle, rgba(0, 212, 255, 0.05) 0%, transparent 70%);
  pointer-events: none;
}

/* Glassmorphic accent line */
.glass-accent-line {
  width: 60px;
  height: 4px;
  background: linear-gradient(90deg, var(--neon-cyan) 0%, var(--neon-pink) 100%);
  border-radius: 2px;
  margin: 16px 0;
}

/* Team member card specific styles */
.team-glass-card {
  text-align: center;
}

.team-glass-card .glass-icon-container {
  width: 100px;
  height: 100px;
  font-size: 48px;
  margin: 0 auto 20px;
  border-radius: 50%;
}

.team-member-name {
  font-size: 20px;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 8px;
}

.team-member-role {
  font-size: 13px;
  color: var(--neon-cyan);
  font-weight: 600;
  margin-bottom: 20px;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.glass-contact-item {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 13px;
  color: rgba(255, 255, 255, 0.7);
  margin-bottom: 12px;
  padding: 8px 0;
  border-bottom: 1px solid rgba(0, 212, 255, 0.1);
}

.glass-contact-item:last-child {
  border-bottom: none;
}

.glass-contact-item i {
  color: var(--neon-cyan);
  font-size: 16px;
  min-width: 20px;
}

.glass-contact-item a {
  color: var(--neon-cyan);
  text-decoration: none;
  transition: color 0.3s;
  cursor: pointer;
}

.glass-contact-item a:hover {
  color: #ffffff;
  text-decoration: underline;
}

/* Feature cards specific */
.feature-glass-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.feature-glass-title {
  font-size: 18px;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 12px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .glass-card {
    padding: 24px;
  }

  .glass-grid,
  .glass-grid-wide {
    grid-template-columns: 1fr;
    gap: 20px;
  }

  .glass-title {
    font-size: 20px;
  }

  .glass-section {
    padding: 60px 20px;
  }
}

/* Animation for appearance */
@keyframes glassFadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.glass-card {
  animation: glassFadeIn 0.6s ease-out;
}

.glass-card:nth-child(2) {
  animation-delay: 0.1s;
}

.glass-card:nth-child(3) {
  animation-delay: 0.2s;
}

.glass-card:nth-child(4) {
  animation-delay: 0.3s;
}

.glass-card:nth-child(5) {
  animation-delay: 0.4s;
}

.glass-card:nth-child(6) {
  animation-delay: 0.5s;
}
