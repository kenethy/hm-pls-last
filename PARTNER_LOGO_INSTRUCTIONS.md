# Partner Logo Carousel - Setup Instructions

## 📁 Directory Structure
Place your partner logo images in: `public/images/logo partner/`

## 🖼️ Supported Image Formats
- PNG (recommended for logos with transparency)
- JPG/JPEG (for solid background logos)
- SVG (vector logos - best quality)
- WebP (modern format with good compression)

## 📏 Recommended Image Specifications
- **Width**: 120-200px
- **Height**: 60-80px
- **Aspect Ratio**: 2:1 or 3:1 (landscape orientation)
- **Background**: Transparent (PNG) or white background
- **File Size**: Under 50KB per logo for optimal loading

## 🔄 How to Replace Placeholder Logos

### Step 1: Add Your Logo Files
1. Create the directory: `public/images/logo partner/`
2. Add your partner logo files with descriptive names:
   ```
   public/images/logo partner/
   ├── shell-logo.png
   ├── castrol-logo.png
   ├── mobil1-logo.png
   ├── brembo-logo.png
   ├── bendix-logo.png
   ├── valeo-logo.png
   ├── exedy-logo.png
   ├── ngk-logo.png
   ├── denso-logo.png
   └── bosch-logo.png
   ```

### Step 2: Update the HTML Code
In `resources/views/pages/spare-parts.blade.php`, replace the placeholder divs with actual image tags:

**Replace this:**
```html
<div class="logo-item">
    <div class="logo-placeholder">
        <span class="brand-text">SHELL</span>
    </div>
</div>
```

**With this:**
```html
<div class="logo-item">
    <img src="{{ asset('images/logo partner/shell-logo.png') }}" 
         alt="Shell" 
         class="partner-logo">
</div>
```

### Step 3: Add CSS for Actual Logos
Add this CSS to the existing styles section:

```css
.partner-logo {
    max-width: 120px;
    max-height: 60px;
    width: auto;
    height: auto;
    object-fit: contain;
    filter: grayscale(100%);
    transition: all 0.3s ease;
    opacity: 0.7;
}

.partner-logo:hover {
    filter: grayscale(0%);
    opacity: 1;
    transform: scale(1.05);
}
```

## 🎨 Customization Options

### Animation Speed
Change the animation duration in CSS:
```css
.logo-carousel-track {
    animation: scroll-logos 30s linear infinite; /* Change 30s to desired speed */
}
```

### Number of Logos
- Add more logo items to display more partners
- Remember to duplicate the set for seamless looping
- Adjust the track width calculation if needed

### Responsive Behavior
The carousel automatically adjusts for different screen sizes:
- Desktop: 150px min-width per logo
- Tablet: 120px min-width per logo  
- Mobile: 100px min-width per logo

## 🔧 Troubleshooting

### Logo Not Displaying
1. Check file path is correct
2. Ensure image file exists in the directory
3. Verify image format is supported
4. Check file permissions

### Animation Issues
1. Clear browser cache
2. Check CSS is properly loaded
3. Verify JavaScript is not blocked

### Performance Issues
1. Optimize image file sizes
2. Use WebP format for better compression
3. Consider lazy loading for many logos

## 📱 Mobile Optimization
The carousel is fully responsive and includes:
- Touch support for pause/resume
- Faster animation on smaller screens
- Smaller logo sizes for mobile devices
- Smooth transitions across all devices

## ♿ Accessibility Features
- Keyboard navigation support
- Focus states for logo items
- Proper alt text for screen readers
- Animation respects user preferences

---

**Note**: After making changes, clear your browser cache and test on different devices to ensure everything works correctly.
