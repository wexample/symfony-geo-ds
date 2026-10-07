import Component from '@wexample/symfony-loader/js/Class/Component';
import LazyActivationMixin from '@wexample/symfony-loader/js/Class/Mixins/LazyActivationMixin';
import { AssetsServiceEvents } from '@wexample/symfony-loader/js/Services/AssetsService';
import maplibregl from 'maplibre-gl';
import type { Map as MapLibreMap, Marker } from 'maplibre-gl';

type GeoMapMarker = {
  lat: number;
  lng: number;
  label: string | null;
  href: string | null;
};

type GeoMapOptions = {
  markers: GeoMapMarker[];
  route: boolean;
  center: [number, number] | null;
  zoom: number | null;
  styleUrl: string;
  routingUrl: string;
  routingProfile: string;
};

type OsrmRoute = {
  distance: number;
  duration: number;
  geometry: { type: 'LineString'; coordinates: [number, number][] };
};

const ROUTE_SOURCE = 'geo-map-route';
// Europe, from far enough to read it: where a map with nothing to show opens.
const DEFAULT_CENTER: [number, number] = [4.35, 50.85];
const DEFAULT_ZOOM = 4;
// A lone pin is shown at the scale of a street, not of a continent.
const SINGLE_MARKER_ZOOM = 14;
const FIT_PADDING = 48;

export default class extends Component {
  // Given by geo_map() (GeoMapExtension), merged in by the loader.
  declare public options: GeoMapOptions;
  private map?: MapLibreMap;
  private markers: Marker[] = [];
  private routeRequest?: AbortController;

  // A map is a library to start: it waits to be seen (`lazy: false` on a call
  // to draw it at once).
  async init() {
    LazyActivationMixin.apply(this);
    await super.init();
  }

  protected async activateListeners(): Promise<void> {
    await super.activateListeners();
    this.app.services.events.listen(AssetsServiceEvents.USAGE_CHANGE, this.onUsageChange);

    const canvas = this.el.querySelector<HTMLElement>('.geo-map--canvas');

    if (!canvas) {
      return;
    }

    const lngLats = this.options.markers.map((marker): [number, number] => [marker.lng, marker.lat]);

    this.map = new maplibregl.Map({
      container: canvas,
      style: this.options.styleUrl,
      center: this.options.center ?? lngLats[0] ?? DEFAULT_CENTER,
      zoom: this.options.zoom ?? (lngLats.length ? SINGLE_MARKER_ZOOM : DEFAULT_ZOOM),
      attributionControl: { compact: true },
    });
    this.map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right');

    this.markers = this.options.markers.map((marker) => this.addMarker(marker));
    this.frame(lngLats);

    if (this.options.route && lngLats.length > 1) {
      this.map.once('load', () => {
        void this.drawRoute(lngLats);
      });
    }
  }

  protected async deactivateListeners(): Promise<void> {
    this.app.services.events.forget(AssetsServiceEvents.USAGE_CHANGE, this.onUsageChange);
    this.routeRequest?.abort();
    this.markers.forEach((marker) => marker.remove());
    this.markers = [];
    this.map?.remove();
    this.map = undefined;

    await super.deactivateListeners();
  }

  private addMarker(marker: GeoMapMarker): Marker {
    const pin = new maplibregl.Marker({ color: this.pinColor() }).setLngLat([marker.lng, marker.lat]);

    if (marker.label) {
      pin.setPopup(new maplibregl.Popup({ offset: 24, closeButton: false }).setDOMContent(this.popupContent(marker)));
    }

    return pin.addTo(this.map!);
  }

  // Built from nodes and not from html: a label is whatever the app stored.
  private popupContent(marker: GeoMapMarker): HTMLElement {
    const content = document.createElement(marker.href ? 'a' : 'span');

    content.textContent = marker.label;
    if (marker.href) {
      content.setAttribute('href', marker.href);
    }

    return content;
  }

  // A theme switched: the pins and the road take the new accent. A pin's
  // colour is set when it is made, so the pins are made again.
  private onUsageChange = (): void => {
    if (!this.map) {
      return;
    }

    this.markers.forEach((marker) => marker.remove());
    this.markers = this.options.markers.map((marker) => this.addMarker(marker));

    if (this.map.getLayer(ROUTE_SOURCE)) {
      this.map.setPaintProperty(ROUTE_SOURCE, 'line-color', this.pinColor());
    }
  };

  // The accent of the design system, read where the map is drawn so that a
  // colour scheme or a theme reaches the pins too.
  private pinColor(): string {
    return getComputedStyle(this.el).getPropertyValue('--accent-9').trim() || '#3b82f6';
  }

  private frame(lngLats: [number, number][]): void {
    if (lngLats.length < 2 || this.options.center) {
      return;
    }

    const bounds = lngLats.reduce(
      (box, lngLat) => box.extend(lngLat),
      new maplibregl.LngLatBounds(lngLats[0], lngLats[0])
    );

    this.map!.fitBounds(bounds, { padding: FIT_PADDING, animate: false });
  }

  private async drawRoute(lngLats: [number, number][]): Promise<void> {
    const summary = this.el.querySelector<HTMLElement>('.geo-map--route');
    const coordinates = lngLats.map((lngLat) => lngLat.join(',')).join(';');
    const url = `${this.options.routingUrl.replace(/\/$/, '')}/route/v1/${this.options.routingProfile}/${coordinates}?overview=full&geometries=geojson`;

    this.routeRequest = new AbortController();

    let route: OsrmRoute | undefined;
    try {
      const response = await fetch(url, { signal: this.routeRequest.signal });
      const answer = await response.json();
      route = answer.code === 'Ok' ? answer.routes?.[0] : undefined;
    } catch (error) {
      // Leaving the page cancels the request: nothing is left to draw on.
      if (error instanceof DOMException && error.name === 'AbortError') {
        return;
      }
    }

    if (!this.map) {
      return;
    }

    if (!route) {
      this.say(summary, summary?.dataset.routeFailed ?? '');
      return;
    }

    this.map.addSource(ROUTE_SOURCE, {
      type: 'geojson',
      data: { type: 'Feature', properties: {}, geometry: route.geometry },
    });
    this.map.addLayer({
      id: ROUTE_SOURCE,
      type: 'line',
      source: ROUTE_SOURCE,
      layout: { 'line-join': 'round', 'line-cap': 'round' },
      paint: { 'line-color': this.pinColor(), 'line-width': 5, 'line-opacity': 0.8 },
    });
    // The road may leave the box the pins make: frame the road instead.
    this.frame(route.geometry.coordinates);

    this.say(summary, (summary?.dataset.routeSummary ?? '')
      .replace('{distance}', this.formatDistance(route.distance))
      .replace('{duration}', this.formatDuration(route.duration)));
  }

  private say(summary: HTMLElement | null, text: string): void {
    if (summary) {
      summary.textContent = text;
      summary.hidden = text === '';
    }
  }

  private formatDistance(meters: number): string {
    const locale = document.documentElement.lang || undefined;

    if (meters < 1000) {
      return new Intl.NumberFormat(locale, { style: 'unit', unit: 'meter', maximumFractionDigits: 0 }).format(meters);
    }

    return new Intl.NumberFormat(locale, { style: 'unit', unit: 'kilometer', maximumFractionDigits: 1 }).format(meters / 1000);
  }

  private formatDuration(seconds: number): string {
    const locale = document.documentElement.lang || undefined;
    const minutes = Math.round(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const unit = (value: number, name: string) => new Intl.NumberFormat(locale, { style: 'unit', unit: name, unitDisplay: 'short' }).format(value);

    return hours ? `${unit(hours, 'hour')} ${unit(minutes % 60, 'minute')}` : unit(minutes, 'minute');
  }
}
