# Tagum City Promotional Website

## Project Structure

```
Promotional Website/
├── index.php
├── Server.php
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── script.js
│   └── images/
│       ├── hero-1.jpg
│       ├── hero-2.jpg
│       ├── hero-3.jpg
│       ├── experience-1.jpg
│       ├── experience-2.jpg
│       ├── experience-3.jpg
│       └── experience-4.jpg
└── README.md (this file)
```

## Features

- **Responsive Navigation Bar** - Sticky nav with logo and menu items
- **Image Carousel** - Auto-rotating hero section with manual controls
- **Explore Section** - Cards showcasing attractions
- **Experiences Section** - Grid of local experiences
- **Planning Section** - Travel tips and information
- **Footer** - Links and copyright

## Color Scheme

- **Dark Green**: #1d5a3d (Primary)
- **Light Green**: #2d7a4d (Accent)
- **Gray**: #6b7280 (Text)
- **White**: #ffffff (Background)
- **Light Gray**: #f3f4f6 (Section background)

## How to Add Images

Add the following images to the `assets/images/` folder:

### Hero Carousel Images (Required)
- `hero-1.jpg` - Natural waterways/landscape image (Full width background)
- `hero-2.jpg` - Cultural/local scene (Full width background)
- `hero-3.jpg` - Adventure/nature image (Full width background)

### Experience Images (Required)
- `experience-1.jpg` - River Tours (250x200px)
- `experience-2.jpg` - Mountain Hiking (250x200px)
- `experience-3.jpg` - Cultural Events (250x200px)
- `experience-4.jpg` - Food Tours (250x200px)

## Carousel Features

- **Auto-rotate**: Changes slide every 6 seconds
- **Manual Navigation**: Click arrows to move between slides
- **Dot Indicators**: Click dots to jump to specific slide
- **Smooth Transitions**: Fade effect between slides

## Responsive Breakpoints

- **Desktop**: Full layout
- **Tablet** (768px and below): Adjusted font sizes and button layout
- **Mobile** (480px and below): Single column layout, vertical button stacks

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Future Enhancements

- Add contact form
- Implement booking system
- Add photo gallery with lightbox
- Integrate Google Maps
- Add testimonials section
- Implement search functionality

## Starting the Server

If using PHP built-in server:
```bash
php -S localhost:8000
```

Then visit: `http://localhost:8000/index.php`
