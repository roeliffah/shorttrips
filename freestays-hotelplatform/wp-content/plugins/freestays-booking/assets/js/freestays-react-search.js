(function () {
    const API_BASE = '/wp-json/freestays/v1';

    function SearchInput({ value, onChange, onSearch }) {
        return (
            <div>
                <input
                    type="text"
                    placeholder="Zoek op plaats, hotelnaam of thema"
                    value={value}
                    onChange={e => onChange(e.target.value)}
                    style={{ width: '100%', marginBottom: 8 }}
                />
                {onSearch && (
                    <button type="button" onClick={onSearch} style={{ marginLeft: 8 }}>
                        Zoeken
                    </button>
                )}
            </div>
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
            <select value={value} onChange={e => onChange(e.target.value)}>
                <option value="">Kies een land</option>
                {countries.map(c => (
                    <option key={c.id} value={c.id}>{c.name}</option>
                ))}
            </select>
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
            <select value={value} onChange={e => onChange(e.target.value)} disabled={!countryId}>
                <option value="">Kies een resort</option>
                {resorts.map(r => (
                    <option key={r.id} value={r.id}>{r.name}</option>
                ))}
            </select>
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
            <select value={value} onChange={e => onChange(e.target.value)} disabled={!resortId}>
                <option value="">Kies een stad</option>
                {cities.map(city => (
                    <option key={city.id} value={city.id}>{city.name}</option>
                ))}
            </select>
        );
    }

    // Filters component (alleen dropdowns)
    function Filters({ onChange, values }) {
        return (
            <div style={{ display: 'flex', gap: 8 }}>
                <CountryDropdown value={values.country} onChange={v => onChange({ ...values, country: v, resort: '', city: '' })} />
                <ResortDropdown countryId={values.country} value={values.resort} onChange={v => onChange({ ...values, resort: v, city: '' })} />
                <CityDropdown resortId={values.resort} value={values.city} onChange={v => onChange({ ...values, city: v })} />
            </div>
        );
    }

    // Volledig zoekformulier
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

            // Zoek op city of op vrije tekst
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
            <form onSubmit={handleSearch}>
                <SearchInput value={search} onChange={setSearch} />
                <Filters values={filters} onChange={setFilters} />
                <div style={{ marginTop: 8 }}>
                    <label>Check-in: <input type="date" value={checkin} onChange={e => setCheckin(e.target.value)} required /></label>
                    <label style={{ marginLeft: 8 }}>Check-out: <input type="date" value={checkout} onChange={e => setCheckout(e.target.value)} required /></label>
                </div>
                <div style={{ marginTop: 8 }}>
                    <label>Volwassenen: <input type="number" min="1" value={adults} onChange={e => setAdults(e.target.value)} /></label>
                    <label style={{ marginLeft: 8 }}>Kinderen: <input type="number" min="0" value={children} onChange={e => setChildren(e.target.value)} /></label>
                </div>
                <button type="submit" disabled={loading} style={{ marginTop: 12 }}>
                    {loading ? 'Zoeken...' : 'Zoek hotels'}
                </button>
                {error && <div style={{ color: 'red', marginTop: 8 }}>{error}</div>}
                {results && (
                    <div style={{ marginTop: 20 }}>
                        <h3>Zoekresultaten</h3>
                        {Array.isArray(results.hotel_html) && results.hotel_html.length > 0 ? (
                            <div dangerouslySetInnerHTML={{ __html: results.hotel_html.join('') }} />
                        ) : (
                            <div>Geen hotels gevonden.</div>
                        )}
                    </div>
                )}
            </form>
        );
    }

    // Alleen filters (dropdowns)
    function FiltersOnly() {
        const [filters, setFilters] = React.useState({ country: '', resort: '', city: '' });
        return <Filters values={filters} onChange={setFilters} />;
    }

    // Alleen zoekveld
    function SearchBarOnly() {
        const [search, setSearch] = React.useState('');
        return <SearchInput value={search} onChange={setSearch} />;
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