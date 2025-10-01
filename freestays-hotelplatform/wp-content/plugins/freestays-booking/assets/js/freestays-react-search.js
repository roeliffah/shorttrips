add_shortcode('freestays_search', 'freestays_search_form_shortcode');

function freestays_search_form_shortcode() {
    <div id="freestays-search-form"></div>
    <script>
        (function () {
            const API_BASE = '/wp-json/freestays/v1';

            function SearchInput({ value, onChange, onSearch }) {
                return (
                    React.createElement('div', null,
                        React.createElement('input', {
                            type: 'text',
                            placeholder: 'Zoek op plaats, hotelnaam of thema',
                            value: value,
                            onChange: e => onChange(e.target.value),
                            style: { width: '100%', marginBottom: 8 }
                        }),
                        onSearch && React.createElement('button', {
                            type: 'button',
                            onClick: onSearch,
                            style: { marginLeft: 8 }
                        }, 'Zoeken')
                    )
                );
            }

            function CountryDropdown({ value, onChange }) {
                const [countries, setCountries] = React.useState([]);
                React.useEffect(() => {
                    fetch(`${API_BASE}/countries`)
                        .then(res => res.json())
                        .then(data => setCountries(Array.isArray(data) ? data : []));
                }, []);
                return (
                    React.createElement('select', {
                        value: value,
                        onChange: e => onChange(e.target.value)
                    },
                        React.createElement('option', { value: '' }, 'Kies een land'),
                        countries.map(c =>
                            React.createElement('option', { key: c.id, value: c.id }, c.name)
                        )
                    )
                );
            }

            function ResortDropdown({ countryId, value, onChange }) {
                const [resorts, setResorts] = React.useState([]);
                React.useEffect(() => {
                    if (!countryId) {
                        setResorts([]);
                        return;
                    }
                    fetch(`${API_BASE}/resorts?country_id=${countryId}`)
                        .then(res => res.json())
                        .then(data => setResorts(Array.isArray(data) ? data : []));
                }, [countryId]);
                return (
                    React.createElement('select', {
                        value: value,
                        onChange: e => onChange(e.target.value),
                        disabled: !countryId
                    },
                        React.createElement('option', { value: '' }, 'Kies een resort'),
                        resorts.map(r =>
                            React.createElement('option', { key: r.id, value: r.id }, r.name)
                        )
                    )
                );
            }

            function CityDropdown({ resortId, value, onChange }) {
                const [cities, setCities] = React.useState([]);
                React.useEffect(() => {
                    if (!resortId) {
                        setCities([]);
                        return;
                    }
                    fetch(`${API_BASE}/cities?resort_id=${resortId}`)
                        .then(res => res.json())
                        .then(data => setCities(Array.isArray(data) ? data : []));
                }, [resortId]);
                return (
                    React.createElement('select', {
                        value: value,
                        onChange: e => onChange(e.target.value),
                        disabled: !resortId
                    },
                        React.createElement('option', { value: '' }, 'Kies een stad'),
                        cities.map(city =>
                            React.createElement('option', { key: city.id, value: city.id }, city.name)
                        )
                    )
                );
            }

            function Filters({ onChange, values }) {
                return (
                    React.createElement('div', { style: { display: 'flex', gap: 8 } },
                        React.createElement(CountryDropdown, {
                            value: values.country,
                            onChange: v => onChange({ ...values, country: v, resort: '', city: '' })
                        }),
                        React.createElement(ResortDropdown, {
                            countryId: values.country,
                            value: values.resort,
                            onChange: v => onChange({ ...values, resort: v, city: '' })
                        }),
                        React.createElement(CityDropdown, {
                            resortId: values.resort,
                            value: values.city,
                            onChange: v => onChange({ ...values, city: v })
                        })
                    )
                );
            }

            function HotelSearchForm() {
                const [search, setSearch] = React.useState('');
                const [filters, setFilters] = React.useState({ country: '', resort: '', city: '' });
                const [checkin, setCheckin] = React.useState('');
                const [checkout, setCheckout] = React.useState('');
                const [adults, setAdults] = React.useState(2);
                const [children, setChildren] = React.useState(0);
                const [loading, setLoading] = React.useState(false);
                const [error, setError] = React.useState('');
                const [results, setResults] = React.useState(null);

                const handleSearch = async (e) => {
                    if (e) e.preventDefault();
                    setError('');
                    setLoading(true);
                    setResults(null);

                    let params = new URLSearchParams({
                        checkin,
                        checkout,
                        adults,
                        children,
                        rooms: 1
                    });

                    if (filters.city) {
                        params.append('city', filters.city);
                    } else if (search) {
                        params.append('city', search);
                    } else {
                        setError('Vul een zoekterm of kies een stad.');
                        setLoading(false);
                        return;
                    }

                    try {
                        const res = await fetch(`${API_BASE}/search-by-city?${params.toString()}`);
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || 'Zoeken mislukt');
                        setResults(data);
                    } catch (err) {
                        setError(err.message);
                    }
                    setLoading(false);
                };

                return (
                    React.createElement('form', { onSubmit: handleSearch },
                        React.createElement(SearchInput, { value: search, onChange: setSearch }),
                        React.createElement(Filters, { values: filters, onChange: setFilters }),
                        React.createElement('div', { style: { marginTop: 8 } },
                            React.createElement('label', null, 'Check-in: ',
                                React.createElement('input', {
                                    type: 'date',
                                    value: checkin,
                                    onChange: e => setCheckin(e.target.value),
                                    required: true
                                })
                            ),
                            React.createElement('label', { style: { marginLeft: 8 } }, 'Check-out: ',
                                React.createElement('input', {
                                    type: 'date',
                                    value: checkout,
                                    onChange: e => setCheckout(e.target.value),
                                    required: true
                                })
                            )
                        ),
                        React.createElement('div', { style: { marginTop: 8 } },
                            React.createElement('label', null, 'Volwassenen: ',
                                React.createElement('input', {
                                    type: 'number',
                                    min: 1,
                                    value: adults,
                                    onChange: e => setAdults(e.target.value)
                                })
                            ),
                            React.createElement('label', { style: { marginLeft: 8 } }, 'Kinderen: ',
                                React.createElement('input', {
                                    type: 'number',
                                    min: 0,
                                    value: children,
                                    onChange: e => setChildren(e.target.value)
                                })
                            )
                        ),
                        React.createElement('button', {
                            type: 'submit',
                            disabled: loading,
                            style: { marginTop: 12 }
                        }, loading ? 'Zoeken...' : 'Zoek hotels'),
                        error && React.createElement('div', { style: { color: 'red', marginTop: 8 } }, error),
                        results && React.createElement('div', { style: { marginTop: 20 } },
                            React.createElement('h3', null, 'Zoekresultaten'),
                            Array.isArray(results.hotel_html) && results.hotel_html.length > 0
                                ? React.createElement('div', { dangerouslySetInnerHTML: { __html: results.hotel_html.join('') } })
                                : React.createElement('div', null, 'Geen hotels gevonden.')
                        )
                    )
                );
            }

            function FiltersOnly() {
                const [filters, setFilters] = React.useState({ country: '', resort: '', city: '' });
                return React.createElement(Filters, { values: filters, onChange: setFilters });
            }

            function SearchBarOnly() {
                const [search, setSearch] = React.useState('');
                return React.createElement(SearchInput, { value: search, onChange: setSearch });
            }

            // Mounters voor shortcodes
            window.FreestaysRenderSearchForm = function (id) {
                ReactDOM.render(React.createElement(HotelSearchForm), document.getElementById(id));
            };
            window.FreestaysRenderFilters = function (id) {
                ReactDOM.render(React.createElement(FiltersOnly), document.getElementById(id));
            };
            window.FreestaysRenderSearchBar = function (id) {
                ReactDOM.render(React.createElement(SearchBarOnly), document.getElementById(id));
            };
        })();
    </script>
    <?php
}

wp_enqueue_script(
    'freestays-react-search',
    plugins_url('assets/js/freestays-react-search.js', __FILE__),
    array('react', 'react-dom'), // als je React via WordPress laadt
    filemtime(plugin_dir_path(__FILE__) . 'assets/js/freestays-react-search.js'),
    true
);