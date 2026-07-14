/**
 * Ashmeera Empowers - Accessibility Features
 * WCAG 2.1 Compliant
 */

// ============================================
// State Management
// ============================================

const accessibilityState = {
    darkMode: localStorage.getItem('ashmeera-dark') === 'true',
    highContrast: localStorage.getItem('ashmeera-highContrast') === 'true',
    largeText: localStorage.getItem('ashmeera-largeText') === 'true',
    textToSpeech: false,
    speechToText: false,
    speechSynthesis: window.speechSynthesis,
    recognition: null,
    currentUtterance: null,
    isListening: false
};

// ============================================
// Initialize Accessibility
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Apply saved settings
    if (accessibilityState.darkMode) toggleDarkMode();
    if (accessibilityState.highContrast) toggleHighContrast();
    if (accessibilityState.largeText) toggleLargeText();
    
    // Initialize Speech Recognition - FIXED
    try {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (SpeechRecognition) {
            accessibilityState.recognition = new SpeechRecognition();
            accessibilityState.recognition.lang = 'en-IN';
            accessibilityState.recognition.continuous = false;
            accessibilityState.recognition.interimResults = true;
            accessibilityState.recognition.maxAlternatives = 1;
            console.log('✅ Speech Recognition initialized');
        } else {
            console.warn('❌ Speech Recognition not supported');
        }
    } catch (e) {
        console.warn('❌ Speech Recognition error:', e);
    }
    
    // Keyboard navigation enhancement
    enhanceKeyboardNavigation();
    
    // Add focus indicators
    addFocusIndicators();
});

// ============================================
// Accessibility Menu Toggle
// ============================================

function toggleAccessibilityMenu() {
    const menu = document.getElementById('accessibilityMenu');
    if (menu) {
        menu.classList.toggle('active');
        const toggle = document.querySelector('.accessibility-toggle');
        if (toggle) {
            toggle.classList.toggle('active');
        }
    }
}

// ============================================
// Dark Mode
// ============================================

function toggleDarkMode() {
    accessibilityState.darkMode = !accessibilityState.darkMode;
    localStorage.setItem('ashmeera-dark', accessibilityState.darkMode);
    
    const html = document.documentElement;
    const body = document.body;
    
    if (accessibilityState.darkMode) {
        html.setAttribute('data-bs-theme', 'dark');
        body.classList.add('dark-mode');
        document.querySelector('.accessibility-toggle')?.setAttribute('data-tooltip', 'Light Mode');
        showToast('🌙 Dark mode enabled', 'info', 2000);
    } else {
        html.setAttribute('data-bs-theme', 'light');
        body.classList.remove('dark-mode');
        document.querySelector('.accessibility-toggle')?.setAttribute('data-tooltip', 'Dark Mode');
        showToast('☀️ Light mode enabled', 'info', 2000);
    }
}

// ============================================
// High Contrast Mode
// ============================================

function toggleHighContrast() {
    accessibilityState.highContrast = !accessibilityState.highContrast;
    localStorage.setItem('ashmeera-highContrast', accessibilityState.highContrast);
    
    const body = document.body;
    
    if (accessibilityState.highContrast) {
        body.classList.add('high-contrast');
        showToast('⚫ High contrast enabled', 'info', 2000);
    } else {
        body.classList.remove('high-contrast');
        showToast('⚪ High contrast disabled', 'info', 2000);
    }
}

// ============================================
// Large Text Mode
// ============================================

function toggleLargeText() {
    accessibilityState.largeText = !accessibilityState.largeText;
    localStorage.setItem('ashmeera-largeText', accessibilityState.largeText);
    
    const body = document.body;
    
    if (accessibilityState.largeText) {
        body.classList.add('large-text');
        showToast('🔍 Large text enabled', 'info', 2000);
    } else {
        body.classList.remove('large-text');
        showToast('📏 Large text disabled', 'info', 2000);
    }
}

// ============================================
// Text-to-Speech
// ============================================

function toggleTextToSpeech() {
    accessibilityState.textToSpeech = !accessibilityState.textToSpeech;
    
    if (accessibilityState.textToSpeech) {
        showToast('🔊 Text-to-Speech enabled. Click any text to read.', 'info', 2000);
        document.addEventListener('click', textToSpeechHandler);
    } else {
        stopSpeech();
        document.removeEventListener('click', textToSpeechHandler);
        showToast('🔇 Text-to-Speech disabled', 'info', 2000);
    }
}

function textToSpeechHandler(e) {
    let target = e.target;
    
    while (target && !target.textContent?.trim()) {
        target = target.parentElement;
    }
    
    if (target && target.textContent?.trim()) {
        speakText(target.textContent.trim());
    }
}

function speakText(text) {
    if (!window.speechSynthesis) {
        showToast('❌ Text-to-Speech not supported in this browser.', 'error');
        return;
    }
    
    stopSpeech();
    
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'en-IN';
    utterance.rate = 1;
    utterance.pitch = 1;
    utterance.volume = 1;
    
    accessibilityState.currentUtterance = utterance;
    window.speechSynthesis.speak(utterance);
}

function stopSpeech() {
    if (window.speechSynthesis) {
        window.speechSynthesis.cancel();
    }
    accessibilityState.currentUtterance = null;
}

// ============================================
// Speech-to-Text - FIXED VERSION
// ============================================

function toggleSpeechToText() {
    // Check if recognition is supported
    if (!accessibilityState.recognition) {
        showToast('❌ Speech-to-Text not supported in this browser. Please use Chrome or Edge.', 'error');
        return;
    }
    
    // Toggle state
    accessibilityState.speechToText = !accessibilityState.speechToText;
    
    if (accessibilityState.speechToText) {
        startSpeechRecognition();
    } else {
        stopSpeechRecognition();
    }
}

function startSpeechRecognition() {
    if (!accessibilityState.recognition) {
        showToast('❌ Speech recognition not available', 'error');
        return;
    }
    
    // Check if already listening
    if (accessibilityState.isListening) {
        showToast('⏳ Already listening...', 'info');
        return;
    }
    
    try {
        const recognition = accessibilityState.recognition;
        
        // Set up event handlers
        recognition.onstart = function() {
            accessibilityState.isListening = true;
            showToast('🎤 Listening... Speak now!', 'info', 3000);
            document.querySelector('.accessibility-toggle')?.classList.add('listening');
        };
        
        recognition.onresult = function(event) {
            console.log('📝 Speech result received:', event);
            
            const transcript = event.results[0][0].transcript;
            console.log('📝 Transcript:', transcript);
            
            // Find active input field
            const activeElement = document.activeElement;
            
            if (activeElement && (activeElement.tagName === 'INPUT' || 
                                 activeElement.tagName === 'TEXTAREA' || 
                                 activeElement.contentEditable === 'true')) {
                activeElement.value = transcript;
                activeElement.dispatchEvent(new Event('input'));
                activeElement.dispatchEvent(new Event('change'));
                showToast(`✅ Typed: "${transcript}"`, 'success', 2000);
            } else {
                showToast(`💬 You said: "${transcript}"`, 'info', 2000);
            }
        };
        
        recognition.onerror = function(event) {
            console.error('❌ Speech recognition error:', event.error);
            accessibilityState.isListening = false;
            document.querySelector('.accessibility-toggle')?.classList.remove('listening');
            
            if (event.error === 'not-allowed') {
                showToast('❌ Microphone access denied. Please allow microphone in browser settings.', 'error');
            } else if (event.error === 'no-speech') {
                showToast('🔇 No speech detected. Please try again.', 'warning');
            } else {
                showToast(`❌ Speech error: ${event.error}`, 'error');
            }
        };
        
        recognition.onend = function() {
            console.log('🛑 Speech recognition ended');
            accessibilityState.isListening = false;
            document.querySelector('.accessibility-toggle')?.classList.remove('listening');
            
            // If still enabled, restart
            if (accessibilityState.speechToText) {
                setTimeout(() => {
                    if (accessibilityState.speechToText) {
                        try {
                            recognition.start();
                        } catch (e) {
                            console.warn('Restart error:', e);
                        }
                    }
                }, 1000);
            }
        };
        
        // Start listening
        recognition.start();
        
    } catch (e) {
        console.error('❌ Speech recognition start error:', e);
        accessibilityState.isListening = false;
        showToast('❌ Error starting speech recognition. Please try again.', 'error');
    }
}

function stopSpeechRecognition() {
    accessibilityState.speechToText = false;
    accessibilityState.isListening = false;
    document.querySelector('.accessibility-toggle')?.classList.remove('listening');
    
    if (accessibilityState.recognition) {
        try {
            accessibilityState.recognition.stop();
            accessibilityState.recognition.onend = null;
        } catch (e) {
            console.warn('Stop error:', e);
        }
    }
    
    showToast('🛑 Speech-to-Text disabled', 'info', 2000);
}

// ============================================
// Keyboard Navigation Enhancement
// ============================================

function enhanceKeyboardNavigation() {
    // Skip link
    const skipLink = document.createElement('a');
    skipLink.href = '#main-content';
    skipLink.className = 'skip-link';
    skipLink.textContent = 'Skip to main content';
    document.body.insertBefore(skipLink, document.body.firstChild);
    
    // Focus trap
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            const focusableElements = document.querySelectorAll(
                'a[href], button, input, textarea, select, details, [tabindex]:not([tabindex="-1"])'
            );
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];
            
            if (e.shiftKey && document.activeElement === firstElement) {
                lastElement.focus();
                e.preventDefault();
            } else if (!e.shiftKey && document.activeElement === lastElement) {
                firstElement.focus();
                e.preventDefault();
            }
        }
        
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.show').forEach(modal => {
                const closeBtn = modal.querySelector('[data-bs-dismiss="modal"]');
                if (closeBtn) closeBtn.click();
            });
        }
    });
}

// ============================================
// Focus Indicators
// ============================================

function addFocusIndicators() {
    const style = document.createElement('style');
    style.textContent = `
        *:focus-visible {
            outline: 3px solid #6C3CE1 !important;
            outline-offset: 2px !important;
            box-shadow: 0 0 0 6px rgba(108, 60, 225, 0.25) !important;
        }
        
        .high-contrast *:focus-visible {
            outline: 3px solid #FFFFFF !important;
            outline-offset: 2px !important;
            box-shadow: 0 0 0 6px rgba(0, 0, 0, 0.5) !important;
        }
        
        .accessibility-toggle.listening {
            background: #EF4444 !important;
            animation: pulse-red 1s ease-in-out infinite;
        }
        
        @keyframes pulse-red {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
    `;
    document.head.appendChild(style);
}

// ============================================
// Reset Accessibility Settings
// ============================================

function resetAccessibility() {
    if (accessibilityState.darkMode) toggleDarkMode();
    if (accessibilityState.highContrast) toggleHighContrast();
    if (accessibilityState.largeText) toggleLargeText();
    if (accessibilityState.textToSpeech) toggleTextToSpeech();
    if (accessibilityState.speechToText) stopSpeechRecognition();
    
    localStorage.removeItem('ashmeera-dark');
    localStorage.removeItem('ashmeera-highContrast');
    localStorage.removeItem('ashmeera-largeText');
    
    document.body.classList.remove('dark-mode', 'high-contrast', 'large-text');
    document.documentElement.setAttribute('data-bs-theme', 'light');
    
    showToast('✅ Accessibility settings reset to default', 'success', 3000);
}

// ============================================
// Export functions
// ============================================

window.toggleAccessibilityMenu = toggleAccessibilityMenu;
window.toggleDarkMode = toggleDarkMode;
window.toggleHighContrast = toggleHighContrast;
window.toggleLargeText = toggleLargeText;
window.toggleTextToSpeech = toggleTextToSpeech;
window.toggleSpeechToText = toggleSpeechToText;
window.resetAccessibility = resetAccessibility;
window.speakText = speakText;
window.stopSpeech = stopSpeech;