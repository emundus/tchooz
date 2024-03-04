export default {
    methods: {
        translate(key) {
            return key.length > 0 && Joomla.Text._(key) ? Joomla.Text._(key) : key;
        },
    },
}