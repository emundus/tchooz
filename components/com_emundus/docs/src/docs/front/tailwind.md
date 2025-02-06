# Tailwind CSS
For the Emundus front end, we use Tailwind CSS. Tailwind CSS is a CSS framework that lets you create custom designs
without writing CSS. It is based on a utility-first approach, which means that you can create designs by combining
predefined CSS classes.
Tailwind also lets you maintain a design system via a `tailwind.config.js` configuration file that lets you define
colours, sizes, fonts, etc.

> [!WARNING]
> To avoid conflicts with the Bootstrap CSS framework used by Joomla, we use the `tw-` prefix for Tailwind CSS classes.

## Resources
- [Official TailwindCSS documentation](https://tailwindcss.com/)

## Extend Variables

The `tailwind.config.js` file is where you add, override, or extend Tailwind’s default variables. Here are a few common
customization methods:

### Customizing Colors

To define your own color palette, add a colors object in the theme section of `tailwind.config.js`. For example:

```js{6,7,8}
// tailwind.config.js
module.exports = {
    theme: {
        extend: {
            colors: {
                primary: '#3490dc',
                secondary: '#ffed4a',
                danger: '#e3342f',
            },
        },
    },
};
```

You can then use these colors in your classes like `tw-bg-primary`, `tw-text-secondary`, etc.

### Customizing Spacing

If you want to add specific spacing values (such as margins, padding, etc.), you can do this under spacing:

```js{6,7,8}
// tailwind.config.js
module.exports = {
    theme: {
        extend: {
            spacing: {
                '72': '18rem',
                '84': '21rem',
                '96': '24rem',
            },
        },
    },
};
```

Now, you can use classes like `tw-mt-72`, `tw-p-84`, etc.

## Overriding Defaults
If you want to override Tailwind’s default settings (not just extend them), place the values directly under theme
without using extend:

```js{5,6,11,12}
// tailwind.config.js
module.exports = {
    theme: {
        // Overridden color
        colors: {
            primary: '#1c3d5a',
            secondary: '#ffaf42',
        },
        // Replaces default 1rem spacing if already defined in Tailwind’s defaults
        spacing: {
            '4': '1rem',
            '8': '2rem',
        },
    },
};
```