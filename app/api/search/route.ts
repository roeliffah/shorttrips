import { NextRequest, NextResponse } from 'next/server';
import { parseStringPromise } from 'xml2js';

export async function POST(req: NextRequest) {
  const body = await req.json();
  const {
    searchType,
    destinationInput,
    countryId,
    regionId,
    cityId,
    checkIn,
    checkOut,
    adults,
    children,
    childrenAges,
    rooms,
    mealId,
    transfer,
    roomtypes,
    review,
    distance,
  } = body;

  const username = process.env.SUNHOTELS_USER!;
  const password = process.env.SUNHOTELS_PASS!;

  let destinationParam = '';
  if (searchType === 'snel') {
    destinationParam = destinationInput;
  } else {
    destinationParam = cityId || regionId || countryId;
  }

  const soapBody = `
    <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
      <soap:Body>
        <SearchV3 xmlns="http://xml.sunhotels.net/15/">
          <userName>${username}</userName>
          <password>${password}</password>
          <destination>${destinationParam}</destination>
          <checkInDate>${checkIn}T00:00:00</checkInDate>
          <checkOutDate>${checkOut}T00:00:00</checkOutDate>
          <rooms>${rooms}</rooms>
          <adults>${adults}</adults>
          <children>${children}</children>
          <childrenAges>${childrenAges && childrenAges.length ? childrenAges.join(',') : ''}</childrenAges>
          ${mealId ? `<mealId>${mealId}</mealId>` : ''}
          ${transfer ? `<transfer>${transfer}</transfer>` : ''}
          ${roomtypes && roomtypes.length ? `<roomtypes>${roomtypes.join(',')}</roomtypes>` : ''}
          ${review ? `<review>${review}</review>` : ''}
          ${distance ? `<distance>${distance}</distance>` : ''}
          <language>en</language>
          <currency>euro</currency>
        </SearchV3>
      </soap:Body>
    </soap:Envelope>
  `;

  const response = await fetch("http://xml.sunhotels.net/15/SOAP/NonStaticXMLAPI.asmx", {
    method: "POST",
    headers: {
      "Content-Type": "text/xml; charset=utf-8",
      "SOAPAction": "http://xml.sunhotels.net/15/SearchV3"
    },
    body: soapBody
  });

  const xml = await response.text();
  const json = await parseStringPromise(xml, { explicitArray: false });

  const hotels = json?.["soap:Envelope"]?.["soap:Body"]?.["SearchV3Response"]?.["SearchV3Result"]?.["Hotels"]?.["Hotel"];
  const results = Array.isArray(hotels)
    ? hotels.map((h: any) => ({
        id: h.HotelId,
        name: h.Name,
        description: h.Description,
        images: h.Images?.Image
          ? Array.isArray(h.Images.Image)
            ? h.Images.Image
            : [h.Images.Image]
          : [],
      }))
    : hotels
      ? [{
          id: hotels.HotelId,
          name: hotels.Name,
          description: hotels.Description,
          images: hotels.Images?.Image
            ? Array.isArray(hotels.Images.Image)
              ? hotels.Images.Image
              : [hotels.Images.Image]
            : [],
        }]
      : [];

  return NextResponse.json({ results });
}