import { reactive } from 'vue';

function normalizeRule(rule) {
    if (typeof rule === 'function') {
        return rule;
    }

    return () => '';
}

export function useFormValidation(schema) {
    const errors = reactive({});
    const touched = reactive({});

    Object.keys(schema).forEach((field) => {
        errors[field] = '';
        touched[field] = false;
    });

    function validateField(field, value, values) {
        const rules = schema[field] ?? [];
        const validators = Array.isArray(rules) ? rules : [rules];

        for (const rule of validators) {
            const message = normalizeRule(rule)(value, values);

            if (message) {
                errors[field] = message;
                return false;
            }
        }

        errors[field] = '';
        return true;
    }

    function validateForm(values) {
        return Object.keys(schema).every((field) => validateField(field, values[field], values));
    }

    function touchField(field) {
        touched[field] = true;
    }

    function resetValidation() {
        Object.keys(schema).forEach((field) => {
            errors[field] = '';
            touched[field] = false;
        });
    }

    return {
        errors,
        touched,
        validateField,
        validateForm,
        touchField,
        resetValidation,
    };
}
